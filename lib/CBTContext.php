<?php

require 'vendor/autoload.php';


class CBTContext implements Behat\Behat\Context\Context
{
    protected static $CONFIG;
    protected static $driver;

    public function __construct($parameters) {

        self::$CONFIG = $parameters;

        $GLOBALS['CBT_USERNAME'] = getenv('CBT_USERNAME');
        if(!$GLOBALS['CBT_USERNAME']) $GLOBALS['CBT_USERNAME'] = self::$CONFIG['user'];

        $GLOBALS['CBT_AUTHKEY'] = getenv('CBT_AUTHKEY');
        if(!$GLOBALS['CBT_AUTHKEY']) $GLOBALS['CBT_AUTHKEY'] = self::$CONFIG['key'];

        // Check if our driver has been created, if not create it
        if( !self::$driver ) {
            self::createDriver();
        }
    }

    public static function createDriver() {

        # Each parallel test we are running will contain  
        $test_run_id = getenv("TEST_RUN_ID") ? getenv("TEST_RUN_ID") : 0; 
        
        # build the webdriver hub URL (e.g. https://username:authkey@crossbrowsertesting.com:80/wd/hub)
        $url = "https://" . $GLOBALS["CBT_USERNAME"] . ":" . $GLOBALS["CBT_AUTHKEY"] . "@" . self::$CONFIG["server"] ."/wd/hub";

        # get the capabilities for this test_run_id 
        # caps contains the os, browser, and resolution
        $browserCaps = self::$CONFIG["browsers"][$test_run_id];
        # pull in capabilities that we want applied to all tests 
        foreach (self::$CONFIG["capabilities"] as $capName => $capValue) {
            if(!array_key_exists($capName, $browserCaps))
                $browserCaps[$capName] = $capValue;
        }

        self::$driver = RemoteWebDriver::create($url, $browserCaps, 120000, 120000);
    }

    /** @AfterSuite */
    public static function tearDown()
    {
        if(self::$driver)
            self::$driver->quit();
    }
}
?>
