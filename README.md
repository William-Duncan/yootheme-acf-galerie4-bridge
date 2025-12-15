# YOOtheme ACF Galerie 4 Bridge

Expose ACF Galerie 4 fields as Multiple Items Sources in YOOtheme Pro.

## Description

This plugin creates a bridge between **ACF Galerie 4** and **YOOtheme Pro**, allowing you to use ACF galleries as dynamic content sources in YOOtheme elements (Gallery, Grid, Slideshow, etc.).

Without this plugin, ACF Galerie 4 fields appear as plain text strings in YOOtheme. With this plugin, they become fully functional **Multiple Items Sources**.

## Requirements

| Dependency | Minimum Version | Link |
|------------|-----------------|------|
| WordPress | 6.0+ | - |
| PHP | 7.4+ | - |
| YOOtheme Pro | 4.0+ | [yootheme.com](https://yootheme.com) |
| Advanced Custom Fields | 5.0+ | [advancedcustomfields.com](https://www.advancedcustomfields.com) |
| ACF Galerie 4 | 1.4+ | [WordPress.org](https://wordpress.org/plugins/acf-galerie-4/) |

> **Note:** ACF Pro is not required. The plugin works with the free version of ACF.

## Installation

1. Download or clone this repository into `wp-content/plugins/`
2. Activate the plugin in WordPress Admin > Plugins
3. That's it! No configuration needed.

```bash
# Via WP-CLI
wp plugin activate yootheme-acf-galerie4-bridge
```

## Usage

### 1. Create an ACF Galerie 4 field

In ACF > Field Groups:
- Add a field of type **Galerie 4**
- Assign it to a content type (e.g., Posts, Pages, CPT)

### 2. Use in YOOtheme Builder

1. Edit a page/post with YOOtheme Builder
2. Add an element that supports multiple sources:
   - **Gallery**
   - **Grid**
   - **Slideshow**
   - **Slider**
3. In the Dynamic Content panel, select **Multiple Items Source**
4. Look for your field under the **"ACF Galerie 4"** group

### Naming Convention

The plugin appends `_gallery` suffix to the ACF field name:

| ACF Field | YOOtheme Source |
|-----------|-----------------|
| `photos` | `photos_gallery` |
| `project_gallery` | `project_gallery_gallery` |
| `images` | `images_gallery` |

## How It Works

### Automatic Detection

The plugin automatically scans all ACF field groups and detects those of type `galerie-4`. For each field found:

1. Identifies associated content types (via ACF location rules)
2. Extends the corresponding GraphQL type in YOOtheme
3. Adds an `[Attachment]` field (list of images)

### Architecture

```
yootheme-acf-galerie4-bridge/
├── yootheme-acf-galerie4-bridge.php    # WordPress bootstrap
├── bootstrap.php                        # YOOtheme module (events)
├── README.md                            # Documentation
└── src/
    └── Listener/
        └── AcfGalerie4SourceListener.php   # Main logic
```

### Hooks Used

| Hook | Priority | Description |
|------|----------|-------------|
| `after_setup_theme` | 20 | Loads the YOOtheme module |
| `source.init` | 10 | Extends the GraphQL schema |

### Data Format

ACF Galerie 4 stores images as a serialized array of IDs:

```php
// In wp_postmeta
meta_key: "photos"
meta_value: "a:3:{i:0;i:123;i:1;i:456;i:2;i:789;}"

// Unserialized
[123, 456, 789]  // Attachment IDs
```

The plugin reads these IDs and returns them in the format expected by YOOtheme.

## Troubleshooting

### Field doesn't appear in YOOtheme

1. **Verify the plugin is active** in Plugins
2. **Clear the YOOtheme cache**:
   ```bash
   rm wp-content/themes/yootheme/cache/schema-*.gql
   ```
3. **Reload the YOOtheme Builder**

### Images don't display correctly

- Verify the images exist in the WordPress Media Library
- Make sure the ACF field contains actual images (not PDF files, etc.)

### Dependency error

The plugin automatically checks dependencies. If one is missing, an admin notice will be displayed.

## Compatibility

- **Supported content types**: Posts, Pages, all Custom Post Types
- **ACF location rules**: Only rules based on `post_type` are detected
- **Multisite**: Not tested

## License

GPL-2.0+ - See [LICENSE](http://www.gnu.org/licenses/gpl-2.0.txt)

## Author

William Duncan - [william-duncan.com](https://www.william-duncan.com)

## Changelog

### 1.0.0
- Initial release
- Automatic detection of ACF Galerie 4 fields
- Integration as Multiple Items Source in YOOtheme Pro
