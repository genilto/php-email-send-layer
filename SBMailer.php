<?php

// Load all the project classes
require_once ( __DIR__ . '/src/SBMailerUtils.php' );
require_once ( __DIR__ . '/src/iSBMailerAdapter.php' );
require_once ( __DIR__ . '/src/SBMailer.php' );

// Function to be used to load the desired adapter
function sbmailer_load_adapter ($adapterCode) {
    require_once ( __DIR__ . "/src/adapters/$adapterCode/$adapterCode.php" );
}
