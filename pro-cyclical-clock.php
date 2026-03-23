<?php
/**
 * Plugin Name: Abstracta Digital - Ultra Precision Clock
 * Description: Fully stable cyclic countdown with full control over Header and Finish messages.
 * Version: 11.0
 * Author: Abstracta Digital
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'elementor/widgets/register', function( $widgets_manager ) {

    class Abstracta_Final_Ultra_Widget extends \Elementor\Widget_Base {

        public function get_name() { return 'abstracta_ultra_clock'; }
        public function get_title() { return 'Abstracta Ultra Clock'; }
        public function get_icon() { return 'eicon-countdown'; }
        public function get_categories() { return [ 'general' ]; }

        protected function register_controls() {
            
            // --- TAB CONTENT: CONFIGURATION ---
            $this->start_controls_section('section_config', [
                'label' => 'Time Configuration',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);

            $this->add_control('cycle_type', [
                'label' => 'Cycle Type',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'weekly',
                'options' => [ 'weekly' => 'Weekly', 'monthly' => 'Monthly' ],
            ]);

            $this->add_control('start_day_week', [
                'label' => 'Start Day',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '4',
                'options' => ['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','7'=>'Sunday'],
                'condition' => ['cycle_type' => 'weekly'],
            ]);

            $this->add_control('end_day_week', [
                'label' => 'End Day',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => ['1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday','4'=>'Thursday','5'=>'Friday','6'=>'Saturday','7'=>'Sunday'],
                'condition' => ['cycle_type' => 'weekly'],
            ]);

            $this->add_control('start_time', [ 'label' => 'Start Time (HH:MM)', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '09:00' ]);
            $this->add_control('end_time', [ 'label' => 'End Time (HH:MM)', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '17:00' ]);

            $this->end_controls_section();

            // --- TAB CONTENT: MESSAGES ---
            $this->start_controls_section('section_messages', [
                'label' => 'Messages Content',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]);
            $this->add_control('header_text', [ 'label' => 'Header Text', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Offer ends in:' ]);
            $this->add_control('finish_text', [ 'label' => 'Finish Text', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Offer Expired!' ]);
            $this->end_controls_section();

            // --- TAB STYLE: HEADER MESSAGE ---
            $this->start_controls_section('style_header', [
                'label' => 'Header Message Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]);
            $this->add_control('header_color', [
                'label' => 'Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .abs-header-msg' => 'color: {{VALUE}};' ],
            ]);
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [ 'name' => 'header_typo', 'selector' => '{{WRAPPER}} .abs-header-msg' ]
            );
            $this->add_responsive_control('header_spacing', [
                'label' => 'Bottom Spacing',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'selectors' => [ '{{WRAPPER}} .abs-header-msg' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
            ]);
            $this->end_controls_section();

            // --- TAB STYLE: FINISH MESSAGE ---
            $this->start_controls_section('style_finish', [
                'label' => 'Finish Message Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]);
            $this->add_control('finish_color', [
                'label' => 'Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .abs-finish-msg' => 'color: {{VALUE}};' ],
            ]);
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [ 'name' => 'finish_typo', 'selector' => '{{WRAPPER}} .abs-finish-msg' ]
            );
            $this->end_controls_section();

            // --- TAB STYLE: NUMBERS & LABELS ---
            $this->start_controls_section('style_clock', [
                'label' => 'Clock Digits & Labels',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]);
            $this->add_control('num_color', [
                'label' => 'Numbers Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .reloj-num' => 'color: {{VALUE}};' ],
            ]);
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [ 'name' => 'num_typo', 'label' => 'Numbers Typo', 'selector' => '{{WRAPPER}} .reloj-num' ]
            );
            $this->add_control('label_color', [
                'label' => 'Labels Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [ '{{WRAPPER}} .reloj-label' => 'color: {{VALUE}};' ],
                'separator' => 'before'
            ]);
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [ 'name' => 'label_typo', 'label' => 'Labels Typo', 'selector' => '{{WRAPPER}} .reloj-label' ]
            );
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $tz_str = get_option('timezone_string') ?: 'Europe/Madrid';
            $tz = new DateTimeZone($tz_str);
            $now = new DateTime('now', $tz);

            $is_active = false;
            $current_day_num = (int)$now->format('N');
            $current_minutes = ($current_day_num * 1440) + (intval($now->format('H')) * 60) + intval($now->format('i'));

            list($sh, $sm) = explode(':', $settings['start_time'] ?: '00:00');
            $start_minutes = (intval($settings['start_day_week']) * 1440) + (intval($sh) * 60) + intval($sm);

            list($eh, $em) = explode(':', $settings['end_time'] ?: '23:59');
            $end_minutes = (intval($settings['end_day_week']) * 1440) + (intval($eh) * 60) + intval($em);

            if ($start_minutes <= $end_minutes) {
                if ($current_minutes >= $start_minutes && $current_minutes <= $end_minutes) $is_active = true;
            } else {
                if ($current_minutes >= $start_minutes || $current_minutes <= $end_minutes) $is_active = true;
            }

            if (!$is_active) {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) echo "<div style='text-align:center; padding:10px; border:1px solid #ccc;'>[Abstracta] Hidden Outside Schedule</div>";
                return;
            }

            $end_dt = new DateTime('today ' . $settings['end_time'], $tz);
            if ($current_day_num != (int)$settings['end_day_week'] || $now > $end_dt) {
                $days = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
                $end_dt->modify('next ' . $days[$settings['end_day_week']] . ' ' . $settings['end_time']);
            }
            $end_ts = $end_dt->getTimestamp() * 1000;
            $id = 'abs-u-' . $this->get_id();
            ?>
            <div id="<?php echo $id; ?>" class="abs-u-wrapper" data-end="<?php echo $end_ts; ?>" style="text-align:center;">
                <?php if(!empty($settings['header_text'])): ?>
                    <div class="abs-header-msg"><?php echo esc_html($settings['header_text']); ?></div>
                <?php endif; ?>
                
                <div class="reloj-container" style="display: flex; gap: 15px; justify-content: center;">
                    <div class="reloj-box"><span class="reloj-num d">00</span><span class="reloj-label">Days</span></div>
                    <div class="reloj-box"><span class="reloj-num h">00</span><span class="reloj-label">Hours</span></div>
                    <div class="reloj-box"><span class="reloj-num m">00</span><span class="reloj-label">Mins</span></div>
                    <div class="reloj-box"><span class="reloj-num s">00</span><span class="reloj-label">Secs</span></div>
                </div>

                <div class="abs-finish-msg" style="display:none;"><?php echo esc_html($settings['finish_text']); ?></div>
            </div>

            <script>
            (function() {
                const w = document.getElementById('<?php echo $id; ?>');
                if(!w) return;
                const endTime = parseInt(w.getAttribute('data-end'));
                const tick = () => {
                    const diff = endTime - new Date().getTime();
                    if (diff <= 0) {
                        if(w.querySelector('.reloj-container')) w.querySelector('.reloj-container').style.display = 'none';
                        if(w.querySelector('.abs-header-msg')) w.querySelector('.abs-header-msg').style.display = 'none';
                        const f = w.querySelector('.abs-finish-msg');
                        if(f && f.innerText.trim() !== "") f.style.display = 'block';
                        else w.style.display = 'none';
                        return;
                    }
                    w.querySelector('.d').innerText = Math.floor(diff / 86400000).toString().padStart(2, '0');
                    w.querySelector('.h').innerText = Math.floor((diff % 86400000) / 3600000).toString().padStart(2, '0');
                    w.querySelector('.m').innerText = Math.floor((diff % 3600000) / 60000).toString().padStart(2, '0');
                    w.querySelector('.s').innerText = Math.floor((diff % 60000) / 1000).toString().padStart(2, '0');
                };
                setInterval(tick, 1000); tick();
            })();
            </script>
            <style>
                .reloj-num { font-size: 2.5rem; font-weight: bold; display: block; line-height: 1; }
                .reloj-label { font-size: 0.7rem; text-transform: uppercase; display: block; }
            </style>
            <?php
        }
    }
    $widgets_manager->register( new Abstracta_Final_Ultra_Widget() );
});
