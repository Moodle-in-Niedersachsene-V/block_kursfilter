<?php
defined('MOODLE_INTERNAL') || die();

class block_kursfilter extends block_base {

    public function init(): void {
        $this->title = get_string('pluginname', 'block_kursfilter');
    }

    public function applicable_formats(): array {
        return [
            'site-index'  => true,
            'course-view' => true,
            'my'          => true,
        ];
    }

    public function has_config(): bool {
        return true;
    }

    public function instance_allow_multiple(): bool {
        return false;
    }

    public function get_content(): stdClass {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $PAGE->requires->js_call_amd('block_kursfilter/filter', 'init', [
            [
                'blockid'   => (int)$this->instance->id,
                'contextid' => (int)$PAGE->context->id,
            ]
        ]);

        $renderer = $PAGE->get_renderer('block_kursfilter');
        $this->content->text   = $renderer->render_block((int)$this->instance->id);
        $this->content->footer = '';

        return $this->content;
    }
}
