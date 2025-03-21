<?php

function authenticate() {
    /*
     * Called by the pre_controller event to authenticate a user via the Joomla website.
     *
     * Users must be logged in to Joomla before using the database; the user information is picked
     * up via the Joomla session cookie and used to find user login, name and club contact roles.
     *
     * On return the following CodeIgniter session data is set:
     *     $session->userid is the logged-in user ID. 0 means not logged in.
     *     $session->login is the login name if logged in
     *     $session->name is the person's name, e.g. 'Richard Lobb'
     *     $session->email is the users's email
     *     $session->hasFullAccess is a boolean true IFF this is a logged in
     *        club officer with at least one of the roles listed in the config parameter
     *        full_access_roles (see config.php).
     *     $session->roles is a list of the roles this user has in the club, as
     *       obtained from the ctcdb 'members_roles' table.
     *
     * See https://codeigniter.com/user_guide/libraries/sessions.html
     */
    log_message('debug', 'Getting user data');
    if ((getenv("CI_ENVIRONMENT") == 'development') &&
        $fixed_user = getenv("DEV_USER_ID")) {
        $is_admin = getenv("DEV_USER_IS_ADMIN");
        $data = [
            'userID' => $fixed_user,
            'login' => getenv("DEV_USER_LOGIN"),
            'roles' => $is_admin ? ["webmaster"] : [],
            'hasFullAccess' => $is_admin,
            'email' => getenv("DEV_USER_EMAIL"),
            'name' => getenv("DEV_USER_NAME") ];
    } else {
        $data = getJoomlaUserData();
    }
    log_message('debug', 'Authentication hook result: ' . print_r($data, true));
    $session = session();
    $session->set($data);
}

/** Get memberId, loginname, fullname and list of committee roles for the
 * user currently logged in to the main CTC website. Note that the memberId
 * is the id of the user in the ctc db members table, not their id on joomla.
 * This is the Joomla 3.0 version of the function. See below for the J1.2 version
 */
function getJoomlaUserData() {
    $joomlaConfig = config('Joomla');
    $output = '';
    $cookie_file = tmpfile();
    fwrite( $cookie_file, serialize($_COOKIE) );
    $cookie_file_name = stream_get_meta_data($cookie_file)['uri'];
    exec( 'php '.$joomlaConfig->joomlaBase . '/getUserID.php '.$cookie_file_name, $output );
    fclose( $cookie_file );
    log_message('debug', 'cookies: '.print_r($_COOKIE, true));
    log_message('debug', 'getUserID returns: '.print_r($output, true));

    $joomlaUser = unserialize($output[0]);


    $userData = array(
        'userID' => 0,
        'login' => '',
        'roles' => array(),
        'hasFullAccess' => false,
        'email' => '',
        'name' => ''
    );

    if ($joomlaUser['id'] != 0) {
        // User is logged into Joomla. Get their member_id, name and login name.
        $userData['login'] = $joomlaUser['username'];
        $userData['name'] = $joomlaUser['name'];
        $userData['email'] = $joomlaUser['email'];

        $db = db_connect();

        $username = $joomlaUser['username'];
        $result = $db->query("SELECT id from members WHERE loginName='$username'");
        if (!$result || $result->getNumRows() !== 1) {
            die("User $user->username not found in database. Please report this to the webmaster");
        }
        $row = $result->getRow();
        $memberID = $row->id;
        $userData['userID'] = $memberID;
        $result = $db->query(
                    "SELECT role
                     FROM members_roles, roles
                     WHERE memberId='$memberID'
                     AND members_roles.roleId = roles.id"
                );

        foreach($result->getResult() as $row) {
            $userData['roles'][] = strtolower($row->role);
        }

        $fullAccessRoles = config('Access')->fullAccessRoles;
        if (count(array_intersect($userData['roles'], $fullAccessRoles)) > 0) {
            $userData['hasFullAccess'] = true;
        }
    }
    return $userData;
}

?>
