<?php
declare(strict_types = 1);

use craft\rector\SetList as CraftSetList;
use Rector\Core\Configuration\Option;
use Rector\Config\RectorConfig;

return static function(RectorConfig $rectorConfig): void {
    // Skip the integrations folder
    $rectorConfig->skip([
        __DIR__ . '/src/integrations',
    ]);

    // Import the Craft 4 upgrade rule set
    $rectorConfig->sets([
        CraftSetList::CRAFT_CMS_40,
        CraftSetList::CRAFT_COMMERCE_40
    ]);
};
