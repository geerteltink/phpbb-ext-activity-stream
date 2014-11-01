<?php
/**
 * @package phpBB Extension - Activity Stream
 * @copyright (c) Geert Eltink
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace xtreamwayz\activity\service;

use Nickvergessen\TrimMessage\TrimMessage;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use phpbb\user;

/**
 * Activity Service Class
 */
class activity_service
{
    /**
     * @var driver_interface
     */
    protected $db;

    /**
     * @var user
     */
    protected $user;

    /**
     * @var auth
     */
    protected $auth;

    /**
     * @var string phpBB root path
     */
    protected $root_path;

    /**
     * @var string PHP extension
     */
    protected $php_ext;

    /**
     * @param driver_interface $db
     * @param user $user
     * @param auth $auth
     * @param $root_path
     * @param $php_ext
     */
    public function __construct(driver_interface $db, user $user, auth $auth, $root_path, $php_ext)
    {
        $this->db        = $db;
        $this->user      = $user;
        $this->auth      = $auth;
        $this->root_path = $root_path;
        $this->php_ext   = $php_ext;
    }

    public function get_activity_stream()
    {
        // Construct the query
        $search_ary = array(
            'SELECT'    => 'p.*, t.*, f.forum_name, u.username, u.user_colour, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height',
            'FROM'      => array(
                POSTS_TABLE     => 'p',
            ),
            'LEFT_JOIN' => array(
                array(
                    'FROM'  => array(USERS_TABLE => 'u'),
                    'ON'    => 'u.user_id = p.poster_id'
                ),
                array(
                    'FROM'  => array(TOPICS_TABLE => 't'),
                    'ON'    => 'p.topic_id = t.topic_id'
                ),
                array(
                    'FROM'  => array(FORUMS_TABLE => 'f'),
                    'ON'    => 'p.forum_id = f.forum_id'
                ),
            ),
            'WHERE'     => $this->db->sql_in_set('t.forum_id', array_keys($this->auth->acl_getf('f_read', true))) . '
                            AND t.topic_status <> ' . ITEM_MOVED . '
                            AND p.post_time > ' . strtotime('6 months ago'),
            'ORDER_BY'  => 'p.post_id DESC',
        );

        // Build query
        $posts = $this->db->sql_build_query('SELECT', $search_ary);
        // Execute query
        $results = $this->db->sql_query_limit($posts, 20);

        // Build the activity stream
        $activity_stream = array();
        while($row = $this->db->sql_fetchrow($results)) {
            $forum_id = $row['forum_id'];
            $topic_id = $row['topic_id'];
            $post_id = $row['post_id'];

            // Set bbcode parse flags
            $post_text_parse_flags = ($row['bbcode_bitfield'] ? OPTION_FLAG_BBCODE : 0) | OPTION_FLAG_SMILIES;

            // Truncate post text
            $trim = new TrimMessage($row['post_text'], $row['bbcode_uid'], 255, '...');
            $row['post_text'] = $trim->message();

            // Construct the data
            $activity_stream[] = array(
                'forum_id'      => $forum_id,
                'forum_url'     => append_sid("{$this->root_path}viewforum.{$this->php_ext}", "f={$forum_id}"),
                'forum_title'    => $row['forum_name'],
                'forum_title_truncated' => truncate_string(
                    censor_text($row['forum_name']), 50, 255, false, $this->user->lang['ELLIPSIS']
                ),

                'topic_id'      => $topic_id,
                'topic_url'     => append_sid("{$this->root_path}viewtopic.{$this->php_ext}", "t={$topic_id}"),
                'topic_title'   => censor_text($row['topic_title']),
                'topic_title_truncated' => truncate_string(
                    censor_text($row['topic_title']), 50, 255, false, $this->user->lang['ELLIPSIS']
                ),

                'is_first_post' => ($row['topic_first_post_id'] == $row['post_id']) ? true : false,

                'post_id'       => $row['post_id'],
                'post_time'        => $this->user->format_date($row['post_time']),
                'post_time_iso'    => gmdate('c', $row['post_time']),
                'post_url'      => append_sid("{$this->root_path}viewtopic.{$this->php_ext}", "p={$post_id}#p{$post_id}"),
                'post_title'    => ($row['post_subject']) ? censor_text($row['post_subject']) : censor_text($row['topic_title']),
                'post_text'     => generate_text_for_display(
                    $row['post_text'],
                    $row['bbcode_uid'],
                    $row['bbcode_bitfield'],
                    $post_text_parse_flags,
                    true
                ),

                'author_name'   => $row['username'],
                'author_full'   => get_username_string(
                    'full',
                    $row['poster_id'],
                    $row['username'],
                    $row['user_colour'],
                    $row['post_username']
                ),
                'author_avatar' => phpbb_get_user_avatar($row)
            );
        }

        return $activity_stream;
    }
}
