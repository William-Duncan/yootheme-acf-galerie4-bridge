<?php
/**
 * ACF Galerie 4 Source Listener
 *
 * Handles the 'source.init' event to add ACF Galerie 4 fields
 * as Multiple Items Sources in YOOtheme Pro.
 *
 * @package YOOtheme\AcfGalerie4Bridge\Listener
 */

namespace YOOtheme\AcfGalerie4Bridge\Listener;

/**
 * Class AcfGalerie4SourceListener
 *
 * This listener extends YOOtheme's GraphQL schema to add
 * ACF Galerie 4 fields as listOf Attachment sources.
 */
class AcfGalerie4SourceListener
{
    /**
     * Handle the source.init event
     *
     * This method is called when YOOtheme initializes its source system.
     * We detect all ACF Galerie 4 fields and add them to their respective
     * content types as Multiple Items Sources.
     *
     * @param object $source The YOOtheme Source object
     */
    public static function handleSourceInit($source)
    {
        // Get all ACF field groups
        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return;
        }

        $fieldGroups = acf_get_field_groups();

        foreach ($fieldGroups as $group) {
            $fields = acf_get_fields($group);

            if (empty($fields)) {
                continue;
            }

            // Find galerie-4 fields in this group
            foreach ($fields as $field) {
                if ($field['type'] !== 'galerie-4') {
                    continue;
                }

                // Get the post types this field group is assigned to
                $postTypes = self::getPostTypesForFieldGroup($group);

                foreach ($postTypes as $postType) {
                    self::addGalleryFieldToType($source, $postType, $field, $group);
                }
            }
        }
    }

    /**
     * Get post types that a field group is assigned to
     *
     * @param array $group ACF field group
     * @return array List of post type names
     */
    private static function getPostTypesForFieldGroup($group)
    {
        $postTypes = [];

        if (empty($group['location'])) {
            return $postTypes;
        }

        // ACF location rules are nested arrays
        // Each top-level array is an OR group
        // Each item in the group is an AND condition
        foreach ($group['location'] as $orGroup) {
            foreach ($orGroup as $rule) {
                if ($rule['param'] === 'post_type' && $rule['operator'] === '==') {
                    $postTypes[] = $rule['value'];
                }
            }
        }

        return array_unique($postTypes);
    }

    /**
     * Add a gallery field to a content type as Multiple Items Source
     *
     * @param object $source   YOOtheme Source object
     * @param string $postType WordPress post type name
     * @param array  $field    ACF field definition
     * @param array  $group    ACF field group
     */
    private static function addGalleryFieldToType($source, $postType, $field, $group)
    {
        // Convert post type to YOOtheme type name (e.g., 'decoration' -> 'Decoration')
        $typeName = self::getYOOthemeTypeName($postType);

        if (!$typeName) {
            return;
        }

        // Field name in snake_case for GraphQL
        $fieldName = self::toSnakeCase($field['name']) . '_gallery';

        // Add the gallery field directly to the content type
        // This makes it available as a Multiple Items Source
        $source->objectType($typeName, [
            'fields' => [
                $fieldName => [
                    'type' => [
                        'listOf' => 'Attachment',
                    ],
                    'metadata' => [
                        'label' => ($field['label'] ?: $field['name']) . ' (Galerie)',
                        'group' => 'ACF Galerie 4',
                    ],
                    'extensions' => [
                        'call' => [
                            'func' => __CLASS__ . '::resolveGallery',
                            'args' => [
                                'field' => $field['name'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Convert WordPress post type to YOOtheme GraphQL type name
     *
     * @param string $postType WordPress post type
     * @return string|null YOOtheme type name or null if not found
     */
    private static function getYOOthemeTypeName($postType)
    {
        // Map common post types
        $map = [
            'post' => 'Post',
            'page' => 'Page',
        ];

        if (isset($map[$postType])) {
            return $map[$postType];
        }

        // For custom post types, convert to PascalCase
        // e.g., 'decoration' -> 'Decoration', 'my_post_type' -> 'MyPostType'
        return self::toPascalCase($postType);
    }

    /**
     * Convert string to PascalCase
     *
     * @param string $string Input string
     * @return string PascalCase string
     */
    private static function toPascalCase($string)
    {
        // Replace underscores and hyphens with spaces
        $string = str_replace(['_', '-'], ' ', $string);
        // Capitalize each word
        $string = ucwords($string);
        // Remove spaces
        return str_replace(' ', '', $string);
    }

    /**
     * Convert string to snake_case
     *
     * @param string $string Input string
     * @return string snake_case string
     */
    private static function toSnakeCase($string)
    {
        // Insert underscore before uppercase letters
        $string = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);
        // Replace hyphens and spaces with underscores
        $string = str_replace(['-', ' '], '_', $string);
        // Convert to lowercase
        return strtolower($string);
    }

    /**
     * Resolve gallery field to array of attachment objects
     *
     * This resolver is called by YOOtheme's GraphQL execution when
     * fetching the field value. It reads the ACF Galerie 4 meta value
     * (stored as a serialized array of attachment IDs) and converts it
     * to an array of WP_Post attachment objects.
     *
     * @param mixed  $post    The current post object or ID
     * @param array  $args    Arguments passed to the resolver (contains 'field' name)
     * @param mixed  $context GraphQL context
     * @param mixed  $info    GraphQL resolve info
     *
     * @return array Array of WP_Post attachment objects
     */
    public static function resolveGallery($post, $args, $context, $info)
    {
        // Get field name from resolver args
        $fieldName = $args['field'] ?? '';

        if (empty($fieldName)) {
            return [];
        }

        // Get post ID from the post object
        $postId = self::getPostId($post);

        if (!$postId) {
            return [];
        }

        // Get the raw meta value
        // ACF Galerie 4 stores values as a serialized PHP array of attachment IDs
        $value = get_post_meta($postId, $fieldName, true);

        // Handle empty values
        if (empty($value)) {
            return [];
        }

        // Handle serialized strings (WordPress auto-unserializes, but be defensive)
        if (is_string($value)) {
            $value = maybe_unserialize($value);
        }

        // Ensure we have an array
        if (!is_array($value)) {
            return [];
        }

        // Convert all values to integers and filter out empty/invalid entries
        $attachmentIds = array_filter(
            array_map('intval', $value),
            function ($id) {
                return $id > 0;
            }
        );

        // Return empty if no valid IDs
        if (empty($attachmentIds)) {
            return [];
        }

        // Validate that these are actual image attachments and preserve order
        $validIds = [];
        foreach ($attachmentIds as $id) {
            if (wp_attachment_is_image($id)) {
                $validIds[] = $id;
            }
        }

        // Return attachment IDs (YOOtheme's AttachmentType expects integer IDs)
        return $validIds;
    }

    /**
     * Extract post ID from various input formats
     *
     * YOOtheme may pass the post as an object, array, or integer.
     * This method normalizes the input to get the post ID.
     *
     * @param mixed $post Post object, array, or ID
     *
     * @return int|null Post ID or null if not found
     */
    private static function getPostId($post)
    {
        // Handle WP_Post object
        if (is_object($post) && isset($post->ID)) {
            return (int) $post->ID;
        }

        // Handle array with ID key
        if (is_array($post) && isset($post['ID'])) {
            return (int) $post['ID'];
        }

        // Handle direct integer ID
        if (is_numeric($post)) {
            return (int) $post;
        }

        // Try ACF's method if available
        if (function_exists('acf_get_valid_post_id')) {
            $validId = acf_get_valid_post_id($post);
            return is_numeric($validId) ? (int) $validId : null;
        }

        return null;
    }
}
