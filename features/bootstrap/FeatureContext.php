<?php

require "vendor/autoload.php";

class FeatureContext extends CBTContext{

  /** @Given /^I am on "([^"]*)"$/ */
  public function iAmOnSite($url) {
    self::$driver->get($url);
  }

  /** @When /^I search for "([^"]*)"$/ */
  public function iSearchFor($searchText) {
    $element = self::$driver->findElement(WebDriverBy::name("q"));
    $element->sendKeys($searchText);
    $element->submit();
    sleep(5);
  }

  /** @Then /^I get title as "([^"]*)"$/ */
  public function iShouldGet($string) {
    $title = self::$driver->getTitle();
    if ((string)  $string !== $title) {
      throw new Exception("Expected title: '". $string. "'' Actual is: '". $title. "'");
    }
  }

  /** @Then /^I should see "([^"]*)"$/ */
  public function iShouldSee($string) {
    $source = self::$driver->getPageSource();
    if (strpos($source, $string) === false) {
      throw new Exception("Expected to see: '". $string. "'' Actual is: '". $source. "'");
    }
  }

  /**
  * @Given /^I go to "([^"]*)"$/
  */
  public function iGoTo($url)
  {
    self::$driver->get($url);
  }

  /**
  * @When /^I fill in "([^"]*)" with "([^"]*)"$/
  */
  public function iFillInWith($cssSelector, $textToType)
  {
    $el = self::$driver->findElement(WebDriverBy::cssSelector($cssSelector));
    $el->click();
    $el->sendKeys($textToType);

  }

  /**
  * @Given /^I press "([^"]*)"$/
  */
  public function iPress($cssSelector)
  {
    self::$driver->findElement(WebDriverBy::cssSelector($cssSelector))->click();
  }

  /**
  * @Then /^I should see "([^"]*)" say "([^"]*)"$/
  */
  public function iShouldSeeSay($cssSelector, $expectedText)
  {
    self::$driver->manage()->timeouts()->implicitlyWait(10);
    $elementText = self::$driver->findElement(WebDriverBy::cssSelector($cssSelector))->getText();
    assert($elementText == $expectedText);
    
  }

  /**
   * @When I take a screenshot
   */
  public function iTakeAScreenshot()
  {
    $sessionId = self::$driver->getSessionId();
    $url = "https://crossbrowsertesting.com/api/v3/selenium/{$sessionId}/snapshots";
    $params = array(
      'selenium_test_id' => $sessionId, // required
      'format' => 'json',
    );
    $result = $this->callApi($url, 'POST', $params);
  }

  /**
   * @param string $api_url
   * @param string $method
   * @param mixed $params
   *
   * @return mixed
   *
   * @throws Exception
   */
  private function callApi($api_url, $method = 'GET', $params = false){
    $apiResult = new stdClass();
    $process = curl_init();
    switch ($method){
      case "POST":
        curl_setopt($process, CURLOPT_POST, 1);
        if ($params){
          curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($params));
          curl_setopt($process, CURLOPT_HTTPHEADER, array('User-Agent: php')); //important
        }
        break;
      case "PUT":
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, "PUT");
        if ($params){
          curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($params));
          curl_setopt($process, CURLOPT_HTTPHEADER, array('User-Agent: php')); //important
        }
        break;
      case 'DELETE':
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, "DELETE");
        break;
      default:
        if ($params){
          $api_url = sprintf("%s?%s", $api_url, http_build_query($params));
        }
    }
    // Optional Authentication:
    curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($process, CURLOPT_USERPWD, self::$CONFIG['user'] . ":" . self::$CONFIG['key']);
    curl_setopt($process, CURLOPT_URL, $api_url);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    $apiResult->content = curl_exec($process);
    $apiResult->httpResponse = curl_getinfo($process);
    $apiResult->errorMessage =  curl_error($process);
    $apiResult->params = $params;
    curl_close($process);
    $paramsString = $params ? http_build_query($params) : '';
    $response = json_decode($apiResult->content);

    if ($apiResult->httpResponse['http_code'] != 200){
      $message = 'Error calling "' . $apiResult->httpResponse['url'] . '" ';
      $message .= (isset($paramsString) ? 'with params "'.$paramsString.'" ' : ' ');
      $message .= '. Returned HTTP status ' . $apiResult->httpResponse['http_code'] . ' ';
      $message .= (isset($apiResult->errorMessage) ? $apiResult->errorMessage : ' ');
      $message .= (isset($response->message) ? $response->message : ' ');
      throw new Exception($message);
    } else {
      $response = json_decode($apiResult->content);

      if (isset($response->status)){
        throw new Exception('Error calling "' . $apiResult->httpResponse['url'] . '"' . (isset($paramsString) ? 'with params "'.$paramsString.'"' : '') . '". ' . $response->message);
      }
    }
    return $response;
  }
}
