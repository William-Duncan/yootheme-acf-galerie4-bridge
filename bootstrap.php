<?php
/**
 * YOOtheme module bootstrap for ACF Galerie 4 Bridge
 *
 * This file defines the YOOtheme module configuration.
 * It registers an event listener for the 'source.init' event
 * to extend GraphQL types with ACF Galerie 4 fields.
 *
 * @package YOOtheme\AcfGalerie4Bridge
 */

namespace YOOtheme\AcfGalerie4Bridge;

use YOOtheme\AcfGalerie4Bridge\Listener\AcfGalerie4SourceListener;

// Include the listener class
require_once __DIR__ . '/src/Listener/AcfGalerie4SourceListener.php';

return [
    /**
     * Event listeners registration
     *
     * The 'source.init' event is emitted when YOOtheme initializes its source system.
     * We use priority 10 to run AFTER YOOtheme's ACF integration (which uses -10),
     * so we can extend the types that have already been created.
     */
    'events' => [
        'source.init' => [
            AcfGalerie4SourceListener::class => ['handleSourceInit', 10],
        ],
    ],
];
