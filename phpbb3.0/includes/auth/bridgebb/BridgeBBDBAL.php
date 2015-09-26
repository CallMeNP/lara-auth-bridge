<?php

class BridgeBBDBAL
{
    public static function getUserByUsername($username)
    {
        global $db;
        $username = mb_strtolower($username);
        $sql = 'SELECT *
            FROM '.USERS_TABLE."
            WHERE LOWER(username) = '".$db->sql_escape($username)."'";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }

    public static function getDefaultGroupID()
    {
        global $db;
        $sql = 'SELECT group_id
        FROM '.GROUPS_TABLE."
        WHERE group_name = '".$db->sql_escape('REGISTERED')."'
            AND group_type = ".GROUP_SPECIAL;
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);

        return $row;
    }
}
