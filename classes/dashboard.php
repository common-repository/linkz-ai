<?php

/**
 * Class responsible for rendering the Linkz.ai dashboard pages.
 */
class LinkzAiDashboard
{
    /** @var LinkzAiDashboard Singleton instance of the class */
    protected static $instance;

    /**
     * Retrieves the singleton instance of the LinkzAiDashboard class.
     *
     * @return LinkzAiDashboard Singleton instance of the class.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Renders a specified dashboard page.
     *
     * @param string  $page_name Name of the page to render.
     * @param array   $args      Arguments to pass to the page renderer.
     * @param boolean $return    Whether to return the output instead of echoing.
     * @return boolean|string    True on success, or the page content if $return is true.
     */
    public function draw_page($page_name, $args = array(), $return = false)
    {
        $page_name = sanitize_key($page_name);

        if (!method_exists($this, "page_{$page_name}")) {
            return false;
        }

        if ($return) {
            ob_start();
        }

        $this->page_header($page_name);

        // Render page content.
        call_user_func(array($this, "page_{$page_name}"), $args);

        $this->page_footer($page_name);

        if ($return) {
            return ob_get_clean();
        }

        return true;
    }

    /**
     * Renders a specified dashboard card.
     *
     * @param string  $card_name Name of the card to render.
     * @param array   $args      Arguments to pass to the card renderer.
     * @param boolean $return    Whether to return the output instead of echoing.
     * @return boolean|string    True on success, or the card content if $return is true.
     */
    public function draw_card($card_name = '', $args = array(), $return = false)
    {
        $card_name = sanitize_key($card_name);

        if (!method_exists($this, "card_{$card_name}")) {
            return false;
        }

        if ($return) {
            ob_start();
        }

        $this->card_header($card_name);

        // Render card content.
        call_user_func(array($this, "card_{$card_name}"), $args);

        $this->card_footer($card_name);

        if ($return) {
            return ob_get_clean();
        }

        return true;
    }

    /**
     * Outputs the header HTML for a dashboard page.
     *
     * @param string $name Optional name of the page.
     * @return void
     */
    private function page_header($name = '')
    {
        ?>
        <div class="linkz-wrap<?php echo $name ? " linkz-" . esc_attr__($name) : ""; ?>">
            <div class="linkz-container">
                <div class="linkz-header">
                    <div class="linkz-logo">
                        <img src="<?php echo esc_url(LINKZ_BASE_URL . 'assets/img/logo.svg'); ?>" alt="Linkz.ai">
                    </div>
                </div>
                <div class="linkz-inside">
        <?php
}

    /**
     * Outputs the footer HTML for a dashboard page.
     *
     * @param string $name Optional name of the page.
     * @return void
     */
    private function page_footer($name = '')
    {
        ?>
                </div><!-- .linkz-inside -->
            </div><!-- .linkz-container -->
            <div class="linkz-side">
                <img src="<?php echo esc_url(LINKZ_BASE_URL . 'assets/img/side-bg.jpg'); ?>" alt="Linkz.ai">
            </div>
        </div><!-- .linkz-wrap -->
        <?php
}

    /**
     * Outputs the header HTML for a dashboard card.
     *
     * @param string $name Optional name of the card.
     * @return void
     */
    private function card_header($name = '')
    {
        ?>
        <div class="linkz-card<?php echo $name ? " linkz-" . esc_attr__($name) : ""; ?>">
        <?php
}

    /**
     * Outputs the footer HTML for a dashboard card.
     *
     * @param string $name Optional name of the card.
     * @return void
     */
    private function card_footer($name = '')
    {
        ?>
        </div><!-- .linkz-card -->
        <?php
}

    /**
     * Renders the welcome page content.
     *
     * @param array $args Arguments for rendering the welcome page.
     * @return void
     */
    private function page_welcome($args = array())
    {
        ?>
        <h1><?php echo __("Welcome to Linkz.ai", 'linkz-ai'); ?></h1>
        <h2><?php echo __("Improve website & blog engagement with link auto-preview popups", 'linkz-ai'); ?></h2>
        <p class="note"><?php echo __("Linkz.ai automatically generates hyperlink previews within your website. Make your website visitors happy & engaged, so that they have no reason to leave", 'linkz-ai'); ?></p>
        <a href="<?php echo esc_url($args['global']['sign_up_url']); ?>"
           class="linkz-btn btn-main"><?php echo __("Sign up", 'linkz-ai'); ?></a>
        <p class="text-center"><?php echo __("Already have a Linkz.ai account?", 'linkz-ai'); ?> <a
                    href="<?php echo esc_url($args['global']['sign_in_url']); ?>"><?php echo __("Log in", 'linkz-ai'); ?></a>
        </p>

        <video class="linkz-showcase" autoplay muted loop>
            <source src="<?php echo esc_url(LINKZ_BASE_URL . 'assets/video/linkz-abstract-demo.mp4'); ?>"
                    type="video/mp4">
        </video>
        <?php
}

    /**
     * Renders the dashboard page content.
     *
     * @param array $args Arguments for rendering the dashboard page.
     * @return void
     */
    private function page_dashboard($args = array())
    {
        if (isset($args['enable_links'])) {
            $this->draw_card('enable_links', $args['enable_links']);
        }
        if (isset($args['plan_data'])) {
            $this->draw_card('plan_usage', $args['plan_data']);
        }
        ?>
        <div class="group in-group">
            <p class="small"><?php echo __("Loving Linkz.ai", 'linkz-ai'); ?> <i style="color:red">♥</i>
                ? <?php echo __("Rate us on the", 'linkz-ai'); ?> <a
                        href="<?php echo esc_url($args['global']['wp_plugin_url']); ?>"
                        target="_blank"><?php echo __("WordPress Plugin Directory", 'linkz-ai'); ?></a></p>
            <a href="<?php echo esc_url($args['global']['logout_url']); ?>"
               class="js-linkz-log-out no-shrink"><?php echo __("Log out", 'linkz-ai'); ?></a>
        </div>
        <?php
}

    /**
     * Renders the 'Plan & Usage' dashboard card.
     *
     * @param array $args Arguments for rendering the card.
     * @return void
     */
    private function card_plan_usage($args = array())
    {
        $progress = $args['n_previews_used'] / $args['n_previews_limit'] * 100;
        $args['n_previews_limit'] = number_format($args['n_previews_limit']);
        $args['n_previews_used'] = number_format($args['n_previews_used']);
        ?>
        <div class="card-title">

            <h1><?php echo __("Plan & Usage", 'linkz-ai'); ?></h1>
        </div>
        <div class="plan">
            <h2><?php echo esc_html($args['plan_name']) . ' ' . __('Plan', 'linkz-ai'); ?>
                <div class="status-badge"><?php echo esc_html($args['plan_status']); ?></div>
            </h2>
            <div class="plan-details">
                <span class="note"><?php echo esc_attr($args['n_previews_limit']); ?> <?php echo __("previews/mo", 'linkz-ai'); ?></span>
                <span class="note"><?php echo esc_attr($args['n_domains']); ?> <?php echo ($args['n_domains'] > 1 ? __('domains', 'linkz-ai') : __('domain', 'linkz-ai')); ?></span>
                <span class="note"><?php echo ($args['branding'] === true) ? __("Linkz.ai Branding", 'linkz-ai') : __("No Branding", 'linkz-ai'); ?></span>
            </div>
        </div>
        <a class="basic-link"
           href="<?php echo esc_url($args['change_plan_url']); ?>"><?php echo __("Change Plan", 'linkz-ai'); ?></a>
        <div class="hr"></div>
        <div class="usage-track-wrap group">
            <div class="track-dates control-line">
                <span class="note"><?php echo date('Y-m-d', $args['period_start']); ?></span>
                <p class="track-label note text-center no-mr small"><?php echo __("CURRENT MONTHLY USAGE", 'linkz-ai'); ?></p>
                <span class="note"><?php echo date('Y-m-d', $args['period_end']); ?></span>
            </div>
            <div class="track"
                 style="--track-progress:<?php echo number_format($progress); ?>%">
                <span class="track-text"><?php echo esc_attr($args['n_previews_used']) . " / " . esc_attr($args['n_previews_limit']) . " " . __("Previews", 'linkz-ai'); ?></span>
            </div>
        </div>
        <?php
if ($args['analytics']) {
            ?>
            <a class="basic-link"
               href="<?php echo esc_url($args['analytics_url']); ?>"><?php echo __("View usage analytics", 'linkz-ai'); ?></a>
            <?php
} else {
            ?>
            <div class="control-line">
                <span class="note small"><?php echo __("View usage analytics", 'linkz-ai'); ?></span>
                <span class="pro-plan-feature"><?php echo __("★ ANALYTICS REQUIRES BLOG PLAN OR HIGHER", 'linkz-ai'); ?></span>
            </div>
            <?php
}
    }

    /**
     * Renders the 'Your Website' dashboard card.
     *
     * @param array $args Arguments for rendering the card.
     * @return void
     */
    private function card_enable_links($args = array())
    {
        ?>
        <div class="card-title">

            <h1><?php echo __("Your Website", 'linkz-ai'); ?></h1>
        </div>
        <div class="control-line">
            <?php $this->draw_toggle(array(
            'classes' => 'js-linkz-enable-toggle',
            'name' => 'linkz-enable',
            'checked' => $args['enabled'],
        ));?>
            <div class="flex-wrap">
                <h3 class="domain-name"><?php echo esc_html($args['domain_name']); ?></h3>
                <div class="preview-toggle js-preview-switch"
                     data-preview-type="<?php echo esc_attr($args['preview_type']); ?>">
                    <!-- Preview Options -->
                </div>
            </div>
        </div>
        <div class="hr"></div>
        <div class="group">
            <a class="basic-link"
               href="<?php echo $args['settings_url']; ?>"><?php echo __("Linkz.ai settings", 'linkz-ai'); ?></a>
            <p class="note no-mr small"><?php echo __('Change where link previews appear on webpages and how they look', 'linkz-ai'); ?></p>
        </div>
        <?php
}

    /**
     * Outputs a toggle switch control.
     *
     * @param array $args Arguments for rendering the toggle.
     * @return void
     */
    private function draw_toggle($args = array())
    {
        $classes = isset($args['classes']) ? esc_attr($args['classes']) : '';
        $label = isset($args['label']) ? esc_attr($args['label']) : '';
        $name = esc_attr($args['name']);
        $id = isset($args['id']) ? esc_attr($args['id']) : $name;
        $checked = isset($args['checked']) && $args['checked'] ? 'checked' : '';
        ?>
        <label class="linz-toggle<?php echo $classes ? " " . $classes : ""; ?>">
            <?php if ($label) {?>
                <span><?php echo $label; ?></span>
            <?php }?>
            <input type="checkbox"
                   name="<?php echo $name; ?>"
                   id="<?php echo $id; ?>"
                <?php echo $checked; ?>>
            <span class="toggle-board"
                  style="--toggle-off-ch:3ch;--toggle-on-ch:2ch;"
                  data-text="<?php echo __("On", 'linkz-ai') . "   " . __("Off", 'linkz-ai'); ?>"></span>
        </label>
        <?php
}
}
