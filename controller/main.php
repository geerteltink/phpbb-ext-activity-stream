<?php
/**
 * @package phpBB Extension - Activity Stream
 * @copyright (c) Geert Eltink
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace xtreamwayz\activity\controller;

use xtreamwayz\activity\service\activity_service;
use phpbb\controller\helper;
use phpbb\template\template;

class main
{
    /**
     * @var helper
     */
    protected $helper;

    /**
     * @var template
     */
    protected $template;

    /**
     * @var activity_service
     */
    protected $activity_service;

    /**
     * Constructor
     *
     * @param helper $helper
     * @param template $template
     * @param activity_service $activity_service
     */
    public function __construct(helper $helper, template $template, activity_service $activity_service)
    {
        $this->helper = $helper;
        $this->template = $template;
        $this->activity_service = $activity_service;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle()
    {
        $activity_stream = $this->activity_service->get_activity_stream();

        // Assign activity stream to the template
        $this->template->assign_var('posts', $activity_stream);

        $this->template->assign_block_vars('navlinks', array(
            'FORUM_NAME'    => 'Activity',
            'U_VIEW_FORUM'  => $this->helper->route('activity_stream')
        ));

        // Render the template
        return $this->helper->render('activity_body.html.twig', 'Recent Activity');
    }
}
