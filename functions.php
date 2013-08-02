<?php
/**
 * Generate the gravatar markup
 * 
 * @global type $forum_gravatar
 * @param int $user_id
 * @return string
 */
function sico_gravatar_generate_avatar_markup_start($user_id)
{
    global $forum_gravatar;
    
    if (file_exists(FORUM_CACHE_DIR.'cache_gravatar.php') && !defined('FORUM_GRAVATAR_LOADED')) {
        include_once(FORUM_CACHE_DIR.'cache_gravatar.php');
    }
    
    if (!defined('FORUM_GRAVATAR_LOADED')) {
        generate_gravatar_cache();
        require FORUM_CACHE_DIR.'cache_gravatar.php';
    }
    
    if(isset($forum_gravatar[$user_id])) {
        return '<img src="'.$forum_gravatar[$user_id].'" alt="" />';
    }
}

function generate_gravatar_cache()
{
    global $forum_db, $forum_config;

    // Select all the users with a gravatar
    $query = array(
        'SELECT'	=> 'id, gravatar, email',
        'FROM'		=> 'users',
        'WHERE'		=> 'id!=1'
    );
    
    // If we are not forcing gravatars only select users who have enabled it
    if ($forum_config['o_gravatar_force'] != 1) {
        $query['WHERE'] .= ' AND gravatar=1';
    }
    
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

    $output = array();

    // Loop through the users and create the gravatar cache
    while ($cur_user = $forum_db->fetch_assoc($result))
    {
        if($cur_user['gravatar'] == 1 || $forum_config['o_gravatar_force'] == 1)
        {
            if(!empty($forum_config['o_gravatar_default']))
                $get_vars[] = 'd='.urlencode($forum_config['o_gravatar_default']);

            if(!empty($forum_config['o_gravatar_rating']))
                $get_vars[] = 'r='.$forum_config['o_gravatar_rating'];

            $get_vars[] = 's='.$forum_config['o_avatars_width'];

            $output[$cur_user['id']] = 'http://www.gravatar.com/avatar/'.md5(strtolower($cur_user['email'])).'.jpg?'.implode("&", $get_vars);
        }
    }

    $fh = @fopen(FORUM_CACHE_DIR.'cache_gravatar.php', 'wb');
    if (!$fh)
        error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);

    fwrite($fh, '<?php'."\n\n".'define(\'FORUM_GRAVATAR_LOADED\', 1);'."\n\n".'global $forum_gravatar;'."\n\n".'$forum_gravatar = '.var_export($output, true).';'."\n\n".'?>');

    fclose($fh);
}
?>