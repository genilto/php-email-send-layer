<?php
// Load Composer's autoloader of project
require_once ( __DIR__ . '/vendor/autoload.php' );

// Load all the project classes
require_once ( __DIR__ . '/src/SBMailerUtils.php' );
require_once ( __DIR__ . '/src/iSBMailerAdapter.php' );
require_once ( __DIR__ . '/src/SBSendgridAdapter.php' );
require_once ( __DIR__ . '/src/SBSendinblueAdapter.php' );
require_once ( __DIR__ . '/src/SBPHPMailerAdapter.php' );
require_once ( __DIR__ . '/src/SBMailer.php' );
