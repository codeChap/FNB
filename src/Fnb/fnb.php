<?php

/**
 * PHP library to automate FNB login and perform a few tasks.
 *
 * @author     Derrick Egersdorfer
 * @license    MIT License
 * @copyright  2014 Derrick Egersdorfer
 * 
 * USAGE :
 *
 * require("fnb/src/Fnb/Fnb.php");
 *
 * $fnb = new Fnb\Fnb(
 *       array(
 *           'username' => 'readOnlyUser',
 *           'password' => 'readOnlyPass',
 *           'verbose' => false,
 *           'write' => true
 *       )
 *   );
 *
 * $fnb->pull();
 *
 * print "<pre>"; print_r($r); print "</pre>";
 *
 */

namespace Fnb;

class Fnb
{
    /**
     * Base url
     */
    private $base_url = "https://www.online.fnb.co.za/";

    /**
     * The login url
     */
    private $login_url = "https://www.online.fnb.co.za/login/Controller";
    
    /**
     * The login path off of the base url
     */
    private $login_two_url = "https://www.online.fnb.co.za/banking/Controller";

    /**
     * My bank accounts button link
     */
    private $my_bank_accounts_url = "https://www.online.fnb.co.za/banking/Controller?nav=accounts.summaryofaccountbalances.navigator.SummaryOfAccountBalances&FARFN=4&actionchild=1&isTopMenu=true&targetDiv=workspace";

    /**
     * Downloads url
     */
    private $downloads_url = "https://www.online.fnb.co.za/banking/Controller?nav=accounts.transactionhistory.navigator.TransactionHistoryDDADownload&downloadFormat=csv";

    /**
     * The username to login with
     */
    private $username = false;

    /**
     * The password to login with
     */
    private $password = false;

    /**
     * Holds Curl
     */
    private $ch;

    /**
     * Holds an array of results from the curl requests
     */
    private $result = array();

    /**
     * Curl should talk more
     */
    private $verbose = false;

    /**
     * I should talk more
     */
    private $write = true;

    /**
     * Are we logged in already?
     */
    private $login = false;

    /**
     * Constrict and set properties
     */
    public function __construct( $config )
    {
        // Get all variables of this class
        $params = array_keys( get_class_vars( get_called_class() ) );

        // Loop and set config values
        foreach( $params as $key ){
            if(array_key_exists($key, $config)){
                if( ! call_user_func(array($this, 'set'.ucFirst(strtolower($key))), $config[$key])){
                    throw new \Exception("Could not set " . $key . " to " . $config[$key] . ": " . implode($this->error) );
                }
            }
        }
    }

    /**
     * Sets the username to login with
     */
    public function setUsername($value)
    {
        // Set value
        $this->username = $value;

        // Done
        return $this;
    }

    /**
     * Sets the password to login with
     */
    public function setPassword($value)
    {
        // Set value
        $this->password = $value;

        // Done
        return $this;
    }

    /**
     * Sets the curl verbose mode to true.
     */
    public function setVerbose($boolean)
    {
        // Set true or false
        if(is_bool($boolean)){
            
            // Set it
            $this->verbose = $boolean;
            
            // Done
            return $this;
        }

        // Oops
        throw new \Exception("Please use a boolean value for verbose setting.");
    }

    /**
     * Sets the script verbose mode to true.
     */
    public function setWrite($boolean)
    {
        // Set true or false
        if(is_bool($boolean)){
            
            // Set it
            $this->write = $boolean;
            
            // Done
            return $this;
        }

        // Oops
        throw new \Exception("Please use a boolean value for write setting.");
    }

    /**
     * Retuens the php temp dir with a trailing slash
     */
    public function getTemp()
    {
        //return rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Function that starts the whole process
     */
    public function pull()
    {
        // Check that we not already logged in
        if($this->login){
            return true;
        }

        // Check for username and password
        if( ! $this->username or ! $this->password){
            throw new \Exception("Username and or password not set, can't login without it.");
        }

        // STEP ONE: Find all inputs of the homepage //

        // Build login fieds
        $fields = array(
            "BrowserType" => "undefined",
            "BrowserVersion" =>  "undefined",
            "LoginButton" => "Login",
            "OperatingSystem" => "undefined",
            "Password" =>    $this->password,
            "Username" =>    $this->username,
            "action" =>  "login",
            "bankingUrl" =>  $this->base_url,
            "country" => "15",
            "countryCode" => "ZA",
            "division" =>  "",  
            "form" =>    "LOGIN_FORM",
            "formname" =>    "LOGIN_FORM",
            "homePageLogin" =>   "true",
            "language" =>   "en",
            "multipleSubmit" =>  "1",
            "nav" => "navigator.UserLogon",
            "products" => "",
            "url" => "0"
        );

        // First call
        $this->write('Login into Fnb - Step 1.');
        $result = $this->request($this->login_url, $fields, "POST");
        $inputs = $this->processHtmlResultToFindInputs($result);

        // Second call
        $this->write('Login into Fnb - Step 2.');
        $new_inputs = array_merge(
            $inputs,
            array(
                "OperatingSystem" => "MacIntel",
                "BrowserType" => "Firefox",
                "BrowserVersion" => "26.0",
                "BrowserHeight" => "0",
                "BrowserWidth" => "0",
                "isMobile" => "false"
            )
        );
        $this->request($this->login_two_url, $new_inputs, "GET");

        // Third call
        $this->write('Clicking on the "My Bank Accounts" Button.');
        $data_array = array(
            "FARFN" =>  4,
            "actionchild" => 1,
            "isTopMenu" => "true",
            "nav" => "accounts.summaryofaccountbalances.navigator.SummaryOfAccountBalances",
            "targetDiv" => "workspace"
        );
        $result = $this->request($this->my_bank_accounts_url, $data_array, "POST");
        $links_to_accounts = $this->processAccountsPageToFindLinks($result);

        // Click on each bank account       
        foreach($links_to_accounts as $link){

            // Count
            $n = isset($n) ? $n + 1 : 1;

            // Build full link
            $link = $this->base_url . $link;

            // Break it up
            parse_str(parse_url($link, PHP_URL_QUERY), $params);

            // Info
            $this->write(' -> ' . 'downloading csv file ('.$n.')');
            
            // Go
            $this->request($link, $params, "POST");

            // Do it
            $this->request($this->downloads_url, array(), "POST", $n.".zip");

            // Unzip it
            $this->unzip($n . ".zip");
        }

        // Now that we have csv files, read them in
        $result = $this->readin();

        // Done
        return $result;
    }

    /**
     * Performs curl requests to FNB
     */
    private function request($url, $postFieldsArray = array(), $method = false, $asFile = false)
    {
        // Fire up curl for first time
        if( ! is_resource($this->ch) ) {

            // Cookie folder and name
            $cookie = $this->getTemp()."fnb_cookie.txt";

            // Start curl
            $this->ch = curl_init();

            // Set curl options
            curl_setopt($this->ch, CURLOPT_VERBOSE, $this->verbose);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->ch, CURLOPT_HEADER, 0);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookie);
            curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookie);
            curl_setopt($this->ch, CURLOPT_COOKIESESSION, true);
        }

        // Handle fields
        $postFields = http_build_query($postFieldsArray);

        // Post request
        switch($method){

            case "POST" :
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($this->ch, CURLOPT_POST, true);
            break;

            case "GET" :
            $url .= "?" .  $postFields;
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, false);
            curl_setopt($this->ch, CURLOPT_POST, false);
            break;

            default :
            throw new \Exception('Please set the correct request type: POST or GET');

        }

        // As a download?
        if($asFile){
            $fp = fopen($this->getTemp() . $asFile, 'w');
            curl_setopt($this->ch, CURLOPT_FILE, $fp);
        }
        
        // Do it
        curl_setopt($this->ch, CURLOPT_URL, $url);
        
        // Get result of the request
        $result = curl_exec($this->ch);

        // Close as download
        if($asFile){
            curl_setopt($this->ch, CURLOPT_FILE, STDOUT);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
            fclose($fp);
        }

        // Done
        return $result;
    }

    /**
     * Processes html requests finds all inputs.
     */
    private function processHtmlResultToFindInputs($html)
    {
        // Parsing the html
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($html);
        $tags = $dom->getElementsByTagName('input');
        $array = array();

        // Loop tags
        foreach($tags as $key => $tag){
            if( $name = $tag->getAttribute('name') ){
                $array[$tag->getAttribute('name')] = $tag->getAttribute('value');
            }
        }

        // Finish up
        if(count($array)){
            return $array;
        }
        else{
            throw new \Exception("Could not process html from the auth request.");
        }
    }

    /**
     * Find links to accounts by looking for a tags with .blackAnchor class
     */
    private function processAccountsPageToFindLinks($html)
    {
        // Parsing the html
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($html);
        $tags = $dom->getElementsByTagName('a');
        $array = array();

        // Loop
        foreach($tags as $key => $tag){

            // Check for hrefs
            if( $onclick = $tag->getAttribute('onclick') ){

                if(preg_match("/nav=transactionhistory/", $onclick)){
                    $array[] = str_replace(
                        array(
                            "fnb.controls.controller.eventsObject.raiseEvent('loadResultScreen','/",
                            "');; return false;"
                        ),
                        array("", ""),
                        $onclick
                    );
                }
            }
        }

        // Finish up
        if(count($array)){
            return array_unique($array);
        }
        else{
            throw new \Exception("Could not process html from the accounts page.");
        }
    }

    /**
     * Function to unzip a file
     */
    private function unzip($file)
    {
        // Build full file path
        $zipFile = $this->getTemp() . $file;

        // Upzup it
        $zip = new \ZipArchive;
        $res = $zip->open($zipFile);
        if ($res === TRUE) {
            $zip->extractTo($this->getTemp());
            $zip->close();
            $this->write(' -> unziped ' . $file);
        }
        else{
            throw new \Exception('Cound not unzip ' . $zipFile);
        }
    }

    /**
     * Scann the temp folder for .csv file and returns them.
     */
    private function readin()
    {
        // Scan temp folder
        if ($handle = opendir($this->getTemp())) {
            while (false !== ($entry = readdir($handle))) {
                if (substr($entry, -4) == '.csv') {
                    $found[] = $entry;
                }
            }
            closedir($handle);
        }

        // Handle CSV
        if(count($found)){

            // Loop found CSV files
            foreach($found as $file){

                // Reset row variables
                $row = 0;
                $trow = 0;

                // Open file
                if (($handle = fopen($this->getTemp().$file, "r")) !== FALSE) {
                    
                    // Read it
                    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                        
                        // Count the row
                        $row = isset($row) ? $row + 1 : 1;

                        // Check the first row for
                        if($row == 1){
                            if( trim($data[0]) !== "ACCOUNT TRANSACTION HISTORY"){
                                $this->write($file . " is not a FNB file, skipping it.");
                                //throw new \Notice("");
                                fclose($handle);
                                continue;
                            }
                        }

                        // Find account number on row 4, col 2 
                        if($row == 4){
                            $account = trim($data[1]);
                        }

                        if($row == 7){

                            // Count data 
                            $num = count($data);

                            // Build headers
                            for ($c=0; $c < $num; $c++) {
                                $headers[$c] = strtolower(trim($data[$c]));
                            } 
                        }

                        // Wanted data starts on row eight, hopefully for all account types?
                        if($row > 7){

                            // Count data 
                            $num = count($data);

                            // Rows from seven onward
                            $trow++;

                            // We assume the order is: date, amount, balance, description - again for all account types.
                            for ($c=0; $c < $num; $c++) {
                                $array[$account][$trow][ $headers[$c] ] = preg_replace('/\s+/', ' ', trim($data[$c]) );
                            }
                        }
                    }

                    // Close file and clean up
                    fclose($handle);

                    // Info
                    $this->write('Extracted from '.$file.'.');
                }
            }

            // Done
            return $array;
        }
    }

    /**
     * Write to the command line
     */
    function write($msg)
    {
        $this->write ? fwrite(STDOUT, "\033[36m" . $msg . "\033[0m" . PHP_EOL) : null;
    }
} 