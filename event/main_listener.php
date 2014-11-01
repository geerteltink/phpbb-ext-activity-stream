<?php
/**
 * @package phpBB Extension - Activity Stream
 * @copyright (c) Geert Eltink
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace xtreamwayz\activity\event;

/**
 * @ignore
 */
use phpbb\controller\helper;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class main_listener implements EventSubscriberInterface
{
    /* @var \phpbb\controller\helper */
    protected $helper;

    /* @var \phpbb\template\template */
    protected $template;

    /**
     * Constructor
     *
     * @param \phpbb\controller\helper $helper Controller helper object
     * @param \phpbb\template\template $template Template object
     */
    public function __construct(helper $helper, template $template)
    {
        $this->helper = $helper;
        $this->template = $template;
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.page_header' => 'add_page_header_link',
        );
    }

    public function add_page_header_link($event)
    {
        $this->template->assign_vars(array(
            'activity_url' => $this->helper->route('activity_stream')
        ));
    }
}
