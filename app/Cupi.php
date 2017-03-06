<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Cookie\FileCookieJar as FileCookieJar;

class Cupi extends Model
{
    // Guzzle API Wrapper for Unity Connection CUPI
    public static function wrapapi($verb, $apiurl, $query = '', $json = '')
    {
        // Wrapper for Guzzle API Calls
        $client = new GuzzleHttpClient();

        $URL = env('UNITYCONNECTION_URL');
        $apiurl = $URL.$apiurl;

        $headers = [
                            'auth'    => [env('UNITYCONNECTION_USER'), env('UNITYCONNECTION_PASS')],
                            'verify'  => false,
                            'headers' => [
                                        'Content-Type'     => 'application/json',
                                        'Accept'           => 'application/json',
                                    ],
                            //'debug' => true,
                            //'http_errors' => true,
                        ];
        if ($query != '') {
            $headers['query'] = $query;
        }
        if ($json != '') {
            $headers['json'] = $json;
        }

        $apiRequest = $client->request($verb, $apiurl, $headers);

        $status_code = $apiRequest->getStatusCode();

        $result = json_decode($apiRequest->getBody()->getContents(), true);

        $response = [
                    'status_code'    => $status_code,
                    'success'        => true,
                    'message'        => '',
                    'response'       => $result,
                    ];

        return $response;

        //return json_decode($apiRequest->getBody()->getContents(), true);
    }

    public static function finduserbyalias($alias)
    {
        $verb = 'GET';
        $apiurl = '/users/';
        $query = ['query' => "(alias is {$alias})"];
        $json = '';

        return self::wrapapi($verb, $apiurl, $query);
    }

    public static function finduseraliasstartswith($alias)
    {
        $verb = 'GET';
        $apiurl = '/users/';
        $query = ['query' => "(alias startswith {$alias})"];
        $json = '';

        return self::wrapapi($verb, $apiurl, $query);
    }

    public static function findmailboxbyextension($extension)
    {
        $verb = 'GET';
        $apiurl = '/users/';
        $query = ['query' => "(DtmfAccessId is {$extension})"];
        $json = '';

        return self::wrapapi($verb, $apiurl, $query);
    }

    public static function listusertemplates()
    {
        $verb = 'GET';
        $apiurl = '/usertemplates';
        $query = '';
        $json = '';

        $return = self::wrapapi($verb, $apiurl, $query);

        return $return;
    }

    public static function listusertemplatenames()
    {
        $verb = 'GET';
        $apiurl = '/usertemplates';
        $query = '';
        $json = '';

        $templates = self::wrapapi($verb, $apiurl, $query);
        $templates = $templates['response']['UserTemplate'];

        $templatenames = [];
        foreach ($templates as $template) {
            $templatenames[] = [
                                    'Alias'    => $template['Alias'],
                                    'ObjectId' => $template['ObjectId'],
                                ];
        }

        return $templatenames;
    }

    public static function createuser($username, $dn, $template)
    {
        $verb = 'POST';
        $apiurl = '/users';
        $query = ['templateAlias' => $template];
        $json = [
                    'Alias'           => $username,
                    'DtmfAccessId'    => $dn,
                ];

        $import = self::wrapapi($verb, $apiurl, $query, $json);

        $user = self::finduserbyalias($username);

        $return = ['return' => $import, 'user' => $user];

        return $return;
    }

    public static function deleteuser($username)
    {

        //$OVERRIDE = "true";
        $userarray = [];
        $userarray['username'] = $username;

        // Check if user has a current mailbox.
        $mailbox = self::finduserbyalias($username);
        $mailbox = $mailbox['response'];

        if (isset($mailbox['@total']) && $mailbox['@total'] == 0) {
            abort(404, 'User not found');
        }

        //return $mailbox;
        $objectid = '';

        if (isset($mailbox['User']['ObjectId'])) {
            $objectid = $mailbox['User']['ObjectId'];
        }

        $verb = 'DELETE';
        $apiurl = "/users/{$objectid}";
        $query = '';
        $json = '';

        $import = self::wrapapi($verb, $apiurl, $query, $json);

        $return = ['return' => $import, 'deleted' => $username];

        return $return;
    }

    public static function updateUserbyobjectid($ID, $UPDATE = [])
    {
        $verb = 'PUT';
        $apiurl = "/users/{$ID}";
        $query = '';
        $json = $UPDATE;

        $import = self::wrapapi($verb, $apiurl, $query, $json);

        $return = ['return' => $import];

        return $return;
    }

    public static function getLDAPUserbyAlias($alias)
    {
        $verb = 'GET';
        $apiurl = '/import/users/ldap';
        $query = ['query' => "(alias startswith {$alias})"];
        $json = '';

        return self::wrapapi($verb, $apiurl, $query);
    }

    public static function importLDAPUser($USERNAME, $DN, $TEMPLATE, $OVERRIDE = '')
    {
        //$OVERRIDE = "true";
        $userarray = [];
        $userarray['username'] = $USERNAME;
        $userarray['new_dn'] = $DN;

        // Check if user has a current mailbox.
        $mailbox = self::finduserbyalias($USERNAME);
        $mailbox = $mailbox['response'];
        if (isset($mailbox['User']['ObjectId'])) {
            $userarray['ObjectId'] = $mailbox['User']['ObjectId'];

            // If the User Mailbox extension is set
            if (isset($mailbox['User']['DtmfAccessId'])) {

                // Set the old dn to the current DTMF Access Method.
                $userarray['old_dn'] = $mailbox['User']['DtmfAccessId'];

                // If override is set to true and the existing and the new mb are different then override the mailbox dn.
                //print $OVERRIDE;
                if ($OVERRIDE == true) {
                    //return $OVERRIDE;

                    if ($userarray['old_dn'] != $userarray['new_dn']) {
                        //print_r($mailbox);
                        $ID = $userarray['ObjectId'];
                        $UPDATE = ['DtmfAccessId' => $userarray['new_dn']];
                        //$UPDATE = ['City' => $userarray['username']];
                        //print_r($UPDATE);
                        $UPDATED = self::updateUserbyobjectid($ID, $UPDATE);
                        //print_r($UPDATED);
                        $UPDATED = self::finduserbyalias($USERNAME);
                        $userarray['updatedmailbox'] = $UPDATED;

                        return $userarray;
                    }
                } else {
                    $userarray['updatemailbox'] = false;
                }

                return $userarray;
            }

        // If not then find the user in LDAP and import user with selected template and new DN.
        } else {
            //echo "Finding User {$userarray['username']}...".PHP_EOL;
            $LDAPUSER = self::getLDAPUserbyAlias($userarray['username']);
            $LDAPUSER = $LDAPUSER['response'];
            //print_r($LDAPUSER);
            if ($LDAPUSER['@total'] >= 1) {
                //echo "User Found: Importing User {$userarray['username']}...".PHP_EOL;
                if ($LDAPUSER['@total'] == 1) {
                    $userarray['ldap'] = $LDAPUSER['ImportUser'];
                } elseif ($LDAPUSER['@total'] > 1) {
                    foreach ($LDAPUSER['ImportUser'] as $USER) {
                        //print_r($USER);
                        if ($USER['alias'] == $userarray['username']) {
                            $userarray['ldap'] = $USER;
                        }
                    }
                }

                // Import User with Site Template.
                $userarray['ldap']['dtmfAccessId'] = $userarray['new_dn'];
                //print_r($userarray);

                $UPDATE = $userarray['ldap'];
                //return $UPDATE;

                //print_r($UPDATE);
                // Build API Request
                $verb = 'POST';
                $apiurl = '/import/users/ldap';
                $query = ['templateAlias' => $TEMPLATE];
                $json = $UPDATE;

                $IMPORT = self::wrapapi($verb, $apiurl, $query, $json);

                //return $IMPORT;
                //$IMPORT = Cupi::importLDAPUser($this->user_template, $userarray['ldap']);
                $IMPORT = self::finduserbyalias($USERNAME);
                $userarray['user_imported'] = $IMPORT;
            } elseif ($LDAPUSER['@total'] == 0) {
                $userarray['error'] = 'No User Found';
            }
        }

        return $userarray;
    }

    public static function listexternalservices()
    {
        $verb = 'GET';
        $apiurl = '/externalservices/';
        $query = '';
        $json = '';

        return self::wrapapi($verb, $apiurl, $query);
    }

    public static function getuserexternalservice($ID)
    {
        $verb = 'GET';
        $apiurl = "/users/{$ID}/externalserviceaccounts";
        $query = '';
        $json = '';

        $return = self::wrapapi($verb, $apiurl, $query, $json);

        $return = ['return' => $return];

        return $return;
    }

    public static function listtimezones()
    {
        $verb = 'GET';
        $apiurl = '/timezones';
        $query = '';
        $json = '';

        return self::wrapapi($verb, $apiurl, $query);
    }

    // Get caller input by object id from the usertemplate.
    public static function get_usertemplate_callerinput_menuentries($objectid)
    {
        $verb = 'GET';
        $apiurl = "/callhandlerprimarytemplates/{$objectid}/menuentries";
        $query = '';
        $json = '';

        $return = self::wrapapi($verb, $apiurl, $query, $json);

        return $return;
    }

    public static function getusertemplate($name)
    {
        $verb = 'GET';
        $apiurl = '/usertemplates';
        $query = ['query' => "(alias is {$name})"];
        $json = '';

        $template = self::wrapapi($verb, $apiurl, $query, $json);
        $template = $template['response'];

        if (isset($template['UserTemplate']['CallHandlerObjectId'])) {
            $objectid = $template['UserTemplate']['CallHandlerObjectId'];
            if ($objectid) {
                $callerinput = self::get_usertemplate_callerinput_menuentries($objectid);
                $template['callerinput'] = $callerinput['response']['MenuEntry'];
            }
        }

        return $template;
    }

    public static function getusertemplatebysite($sitecode)
    {
        $verb = 'GET';
        $apiurl = '/usertemplates';
        $query = ['query' => "(alias startswith {$sitecode})"];
        $json = '';

        $templates = self::wrapapi($verb, $apiurl, $query, $json);
        $templates = $templates['response'];

        if (isset($templates['@total']) && $templates['@total'] == 0) {
            abort(404, 'No UserTemplates found');
        } elseif (isset($templates['@total']) && $templates['@total'] == 1) {
            if (isset($templates['UserTemplate']['CallHandlerObjectId'])) {
                $objectid = $templates['UserTemplate']['CallHandlerObjectId'];
                $callerinput = self::get_usertemplate_callerinput_menuentries($objectid);
                $templates['UserTemplate']['callerinput'] = $callerinput;
                $template_array[] = $templates['UserTemplate'];
            }
        } elseif (isset($templates['@total']) && $templates['@total'] >= 2) {
            foreach ($templates['UserTemplate'] as $template) {
                print_r($template);
                if (isset($template['CallHandlerObjectId'])) {
                    $objectid = $template['CallHandlerObjectId'];
                    $callerinput = self::get_usertemplate_callerinput_menuentries($objectid);
                    $template['callerinput'] = $callerinput;
                    $template_array[] = $template;
                }
            }
        }

        $templates = $template_array;

        return $templates;
    }

    public static function createusertemplate($sitecode, $template, $copytemplate, $operator)
    {
        $name = $template['Alias'];

        $verb = 'POST';
        $apiurl = '/usertemplates';
        $query = ['templateAlias' => $copytemplate];

        // Send in JSON to build the template
        $json = $template;

        /*$json = [
                    'Alias'           => $username,
                    'DisplayName'    => $dn,
                ];*/

        $return = self::wrapapi($verb, $apiurl, $query, $json);

        // return $return;

        // Check if user has a current template.
        $template = self::getusertemplate($name);

        if (isset($template['@total']) && $template['@total'] == 0) {
            abort(404, 'UserTemplate not found');
        }

        //return $template;
        $objectid = '';

        if (isset($template['UserTemplate']['CallHandlerObjectId'])) {
            $objectid = $template['UserTemplate']['CallHandlerObjectId'];
        }

        //return $objectid;

        if (isset($operator) && $operator) {
            if (isset($objectid) && $objectid) {
                $update = self::update_usertemplate_operator($objectid, $sitecode, $operator);

                $template['update_operator'] = $update;
            }
        }

        return $template;
    }

    public static function update_usertemplate_operator($objectid, $sitecode, $operator)
    {
        $verb = 'PUT';
        $apiurl = "/callhandlerprimarytemplates/{$objectid}/menuentries/0";
        $query = '';
        $json = [
                  'Locked'                => 'false',
                  'Action'                => '7',
                  'TransferNumber'        => "{$operator}",
                  'DisplayName'           => "{$sitecode} Operator",
                  'TransferType'          => '0',
                  'TransferRings'         => '4',
                ];

        $return = self::wrapapi($verb, $apiurl, $query, $json);

        return $return;
    }

    public static function delete_usertemplate($name)
    {

        // Check if user has a current template.
        $template = self::getusertemplate($name);

        if (isset($template['@total']) && $template['@total'] == 0) {
            abort(404, 'User Template not found');
        }

        //return $template;
        $objectid = '';

        if (isset($template['UserTemplate']['ObjectId'])) {
            $objectid = $template['UserTemplate']['ObjectId'];
        }

        $verb = 'DELETE';
        $apiurl = "/usertemplates/{$objectid}";
        $query = '';
        $json = '';

        $import = self::wrapapi($verb, $apiurl, $query, $json);

        $return = ['deleted' => $name];

        return $return;
    }
}
