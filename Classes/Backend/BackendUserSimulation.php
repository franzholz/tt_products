<?php

namespace JambageCom\TtProducts\Backend;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;

/**
 * TYPO3 backend user simulation
 * This class is only needed in TYPO3 6.2 to use the \TYPO3\CMS\Core\DataHandling\DataHandler in the Front End.
 *
 * @author Franz Holzinger <franz@ttproducts.de>
 *
 * @internal
 */
class BackendUserSimulation extends AbstractUserAuthentication
{
    /**
     * User workspace.
     * -99 is ERROR (none available)
     * -1 is offline
     * 0 is online
     * >0 is custom workspaces.
     *
     * @var int
     */
    public $workspace = -99;

    /**
     * Custom workspace record if any.
     *
     * @var array
     *
     * @todo Define visibility
     */
    public $workspaceRec = [];

    /**
     * Contains last error message.
     *
     * @var string
     *
     * @todo Define visibility
     */
    public $errorMsg = '';

    /**
     * @var int
     */
    public $firstMainGroup = 0;

    /**
     * Constructor.
     */
    public function __construct($userid)
    {
        parent::__construct();
        $this->user['username'] = 'tt_products_user' . $userid;
        $this->user['uid'] = intval($userid);
        $this->user['admin'] = '1';
    }

    /**
     * Returns true if user is admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return true;
    }

    /**
     * Checks if the page id, $id, is found within the webmounts set up for the user.
     * This should ALWAYS be checked for any page id a user works with, whether it's about reading, writing or whatever.
     * The point is that this will add the security that a user can NEVER touch parts outside his mounted
     * pages in the page tree. This is otherwise possible if the raw page permissions allows for it.
     * So this security check just makes it easier to make safe user configurations.
     * If the user is admin OR if this feature is disabled
     * (fx. by setting TYPO3_CONF_VARS['BE']['lockBeUserToDBmounts']=0) then it returns "1" right away
     * Otherwise the function will return the uid of the webmount which was first found in the rootline of the input page $id.
     *
     * @param int $id Page ID to check
     * @param string $readPerms Content of "->getPagePermsClause(1)" (read-permissions). If not set, they will be internally calculated (but if you have the correct value right away you can save that database lookup!)
     * @param bool|int $exitOnError if set, then the function will exit with an error message
     *
     * @return int|null The page UID of a page in the rootline that matched a mount point
     *
     * @throws \RuntimeException
     *
     * @todo Define visibility
     */
    public function isInWebMount($id, $readPerms = '', $exitOnError = 0)
    {
        return 1;
    }

    /**
     * Returns a WHERE-clause for the pages-table where user permissions according to input argument, $perms, is validated.
     * $perms is the "mask" used to select. Fx. if $perms is 1 then you'll get all pages that a user can actually see!
     * 2^0 = show (1)
     * 2^1 = edit (2)
     * 2^2 = delete (4)
     * 2^3 = new (8)
     * If the user is 'admin' " 1=1" is returned (no effect)
     * If the user is not set at all (->user is not an array), then " 1=0" is returned (will cause no selection results at all)
     * The 95% use of this function is "->getPagePermsClause(1)" which will
     * return WHERE clauses for *selecting* pages in backend listings - in other words this will check read permissions.
     *
     * @param int $perms Permission mask to use, see function description
     *
     * @return string Part of where clause. Prefix " AND " to this.
     *
     * @todo Define visibility
     */
    public function getPagePermsClause($perms)
    {
        return ' 1=1';
    }

    /**
     * Checking the authMode of a select field with authMode set.
     *
     * @param string $table Table name
     * @param string $field Field name (must be configured in TCA and of type "select" with authMode set!)
     * @param string $value Value to evaluation (single value, must not contain any of the chars ":,|")
     * @param string $authMode Auth mode keyword (explicitAllow, explicitDeny, individual)
     *
     * @return bool Whether access is granted or not
     *
     * @todo Define visibility
     */
    public function checkAuthMode($table, $field, $value, $authMode)
    {
        return true;
    }

    /**
     * Checking if a language value (-1, 0 and >0 for sys_language records) is allowed to be edited by the user.
     *
     * @param int $langValue Language value to evaluate
     *
     * @return bool returns true if the language value is allowed, otherwise false
     *
     * @todo Define visibility
     */
    public function checkLanguageAccess($langValue)
    {
        return true;
    }

    /**
     * Checking if a user has editing access to a record from a $GLOBALS['TCA'] table.
     * The checks does not take page permissions and other "environmental" things into account.
     * It only deal with record internals; If any values in the record fields disallows it.
     * For instance languages settings, authMode selector boxes are evaluated (and maybe more in the future).
     * It will check for workspace dependent access.
     * The function takes an ID (integer) or row (array) as second argument.
     *
     * @param string $table Table name
     * @param mixed $idOrRow If integer, then this is the ID of the record. If Array this just represents fields in the record.
     * @param bool $newRecord Set, if testing a new (non-existing) record array. Will disable certain checks that doesn't make much sense in that context.
     * @param bool $deletedRecord set, if testing a deleted record array
     * @param bool $checkFullLanguageAccess Set, whenever access to all translations of the record is required
     *
     * @return bool true if OK, otherwise false
     *
     * @todo Define visibility
     */
    public function recordEditAccessInternals($table, $idOrRow, $newRecord = false, $deletedRecord = false, $checkFullLanguageAccess = false)
    {
        return true; // editing is always allowed
    }

    /**
     * Checking if editing of an existing record is allowed in current workspace if that is offline.
     * Rules for editing in offline mode:
     * - record supports versioning and is an offline version from workspace and has the corrent stage
     * - or record (any) is in a branch where there is a page which is a version from the workspace
     *   and where the stage is not preventing records.
     *
     * @param string $table Table of record
     * @param array $recData Integer (record uid) or array where fields are at least: pid, t3ver_wsid, t3ver_stage (if versioningWS is set)
     *
     * @return string String error code, telling the failure state. false=All ok
     *
     * @todo Define visibility
     */
    public function workspaceCannotEditRecord($table, $recData)
    {
        return false; // editing is always allowed
    }

    /**
     * Check if "live" records from $table may be created or edited in this PID.
     * If the answer is false it means the only valid way to create or edit records in the PID is by versioning
     * If the answer is 1 or 2 it means it is OK to create a record, if -1 it means that it is OK in terms
     * of versioning because the element was within a versionized branch
     * but NOT ok in terms of the state the root point had!
     *
     * @param int $pid PID value to check for. OBSOLETE!
     * @param string $table Table name
     *
     * @return mixed Returns false if a live record cannot be created and must be versionized in order to do so. 2 means a) Workspace is "Live" or workspace allows "live edit" of records from non-versionized tables (and the $table is not versionizable). 1 and -1 means the pid is inside a versionized branch where -1 means that the branch-point did NOT allow a new record according to its state.
     *
     * @todo Define visibility
     */
    public function workspaceAllowLiveRecordsInPID($pid, $table)
    {
        return 2;
    }

    /**
     * Evaluates if auto creation of a version of a record is allowed.
     *
     * @param string $table Table of the record
     * @param int $id UID of record
     * @param int $recpid PID of record
     *
     * @return bool true if ok
     *
     * @todo Define visibility
     */
    public function workspaceAllowAutoCreation($table, $id, $recpid)
    {
        return false; // no support for workspaces
    }

    /**
     * Returns the value/properties of a TS-object as given by $objectString, eg. 'options.dontMountAdminMounts'
     * Nice (general!) function for returning a part of a TypoScript array!
     *
     * @param string $objectString Pointer to an "object" in the TypoScript array, fx. 'options.dontMountAdminMounts'
     * @param array|string $config Optional TSconfig array: If array, then this is used and not $this->userTS. If not array, $this->userTS is used.
     *
     * @return array an array with two keys, "value" and "properties" where "value" is a string with the value of the object string and "properties" is an array with the properties of the object string
     *
     * @todo Define visibility
     */
    public function getTSConfig($objectString, $config = '')
    {
        if (!is_array($config)) {
            // Getting Root-ts if not sent
            $config = $this->userTS;
        }
        $TSConf = ['value' => null, 'properties' => null];
        $parts = GeneralUtility::trimExplode('.', $objectString, true, 2);
        $key = $parts[0];
        if (strlen($key) > 0) {
            if (count($parts) > 1 && strlen($parts[1]) > 0) {
                // Go on, get the next level
                if (is_array($config[$key . '.'])) {
                    $TSConf = $this->getTSConfig($parts[1], $config[$key . '.']);
                }
            } else {
                $TSConf['value'] = $config[$key];
                $TSConf['properties'] = $config[$key . '.'];
            }
        }

        return $TSConf;
    }

    /**
     * Returns the "value" of the $objectString from the BE_USERS "User TSconfig" array.
     *
     * @param string $objectString Object string, eg. "somestring.someproperty.somesubproperty
     *
     * @return string The value for that object string (object path)
     *
     * @see 	getTSConfig()
     *
     * @todo Define visibility
     */
    public function getTSConfigVal($objectString)
    {
        $TSConf = $this->getTSConfig($objectString);

        return $TSConf['value'];
    }

    /**
     * Returns the "properties" of the $objectString from the BE_USERS "User TSconfig" array.
     *
     * @param string $objectString Object string, eg. "somestring.someproperty.somesubproperty
     *
     * @return array The properties for that object string (object path) - if any
     *
     * @see 	getTSConfig()
     *
     * @todo Define visibility
     */
    public function getTSConfigProp($objectString)
    {
        $TSConf = $this->getTSConfig($objectString);

        return $TSConf['properties'];
    }

    /**
     * Writes an entry in the logfile/table
     * Documentation in "TYPO3 Core API".
     *
     * @param int $type Denotes which module that has submitted the entry. See "TYPO3 Core API". Use "4" for extensions.
     * @param int $action Denotes which specific operation that wrote the entry. Use "0" when no sub-categorizing applies
     * @param int $error Flag. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
     * @param int $details_nr The message number. Specific for each $type and $action. This will make it possible to translate errormessages to other languages
     * @param string $details Default text that follows the message (in english!). Possibly translated by identification through type/action/details_nr
     * @param array $data Data that follows the log. Might be used to carry special information. If an array the first 5 entries (0-4) will be sprintf'ed with the details-text
     * @param string $tablename Table name. Special field used by tce_main.php.
     * @param int|string $recuid Record UID. Special field used by tce_main.php.
     * @param int|string $recpid Record PID. Special field used by tce_main.php. OBSOLETE
     * @param int $event_pid The page_uid (pid) where the event occurred. Used to select log-content for specific pages.
     * @param string $NEWid Special field used by tce_main.php. NEWid string of newly created records.
     * @param int $userId alternative Backend User ID (used for logging login actions where this is not yet known)
     *
     * @return int log entry ID
     *
     * @todo Define visibility
     */
    public function writelog($type, $action, $error, $details_nr, $details, $data, $tablename = '', $recuid = '', $recpid = '', $event_pid = -1, $NEWid = '', $userId = 0)
    {
        if (!$userId && isset($this->user['uid'])) {
            $userId = $this->user['uid'];
        }

        $fields_values = [
            'userid' => (int)$userId,
            'type' => (int)$type,
            'action' => (int)$action,
            'error' => (int)$error,
            'details_nr' => (int)$details_nr,
            'details' => $details,
            'log_data' => serialize($data),
            'tablename' => $tablename,
            'recuid' => (int)$recuid,
            'IP' => (string)GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'tstamp' => time(),
            'event_pid' => (int)$event_pid,
            'NEWid' => $NEWid,
            'workspace' => $this->workspace,
        ];
        $this->db->exec_INSERTquery('sys_log', $fields_values);

        return $this->db->sql_insert_id();
    }

    /**
     * Simple logging function.
     *
     * @param string $message Log message
     * @param string $extKey Option extension key / module name
     * @param int $error Error level. 0 = message, 1 = error (user problem), 2 = System Error (which should not happen), 3 = security notice (admin)
     *
     * @return int Log entry UID
     *
     * @todo Define visibility
     */
    public function simplelog($message, $extKey = '', $error = 0)
    {
        return $this->writelog(4, 0, $error, 0, ($extKey ? '[' . $extKey . '] ' : '') . $message, []);
    }

    /**
     * Sends a warning to $email if there has been a certain amount of failed logins during a period.
     * If a login fails, this function is called. It will look up the sys_log to see if there
     * have been more than $max failed logins the last $secondsBack seconds (default 3600).
     * If so, an email with a warning is sent to $email.
     *
     * @param string $email Email address
     * @param int $secondsBack Number of sections back in time to check. This is a kind of limit for how many failures an hour for instance.
     * @param int $max Max allowed failures before a warning mail is sent
     *
     * @access private
     *
     * @todo Define visibility
     */
    public function checkLogFailures($email, $secondsBack = 3600, $max = 3)
    {
        if ($email) {
            // Get last flag set in the log for sending
            $theTimeBack = $GLOBALS['EXEC_TIME'] - $secondsBack;
            $res = $this->db->exec_SELECTquery('tstamp', 'sys_log', 'type=255 AND action=4 AND tstamp>' . (int)$theTimeBack, '', 'tstamp DESC', '1');
            if ($testRow = $this->db->sql_fetch_assoc($res)) {
                $theTimeBack = $testRow['tstamp'];
            }
            $this->db->sql_free_result($res);
            // Check for more than $max number of error failures with the last period.
            $res = $this->db->exec_SELECTquery('*', 'sys_log', 'type=255 AND action=3 AND error<>0 AND tstamp>' . (int)$theTimeBack, '', 'tstamp');
            if ($this->db->sql_num_rows($res) > $max) {
                // OK, so there were more than the max allowed number of login failures - so we will send an email then.
                $subject = 'TYPO3 Login Failure Warning (at ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ')';
                $email_body = 'There have been some attempts (' . $this->db->sql_num_rows($res) . ') to login at the TYPO3
site "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '" (' . GeneralUtility::getIndpEnv('HTTP_HOST') . ').

This is a dump of the failures:

';
                while ($testRows = $this->db->sql_fetch_assoc($res)) {
                    $theData = unserialize($testRows['log_data']);
                    $email_body .= date(
                        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
                        $testRows['tstamp']
                    ) . ':  ' . @sprintf($testRows['details'], (string)$theData[0], (string)$theData[1], (string)$theData[2]);
                    $email_body .= LF;
                }
                $from = MailUtility::getSystemFrom();
                /** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
                $mail = GeneralUtility::makeInstance(MailMessage::class);
                $mail->setTo($email)->setFrom($from)->setSubject($subject)->text($email_body);
                $mail->send();
                // Logout written to log
                $this->writelog(255, 4, 0, 3, 'Failure warning (%s failures within %s seconds) sent by email to %s', [$this->db->sql_num_rows($res), $secondsBack, $email]);
                $this->db->sql_free_result($res);
            }
        }
    }
}
