<?php
defined('MOODLE_INTERNAL') || die();

class block_kursfilter_renderer extends plugin_renderer_base {

    public function render_block(int $blockid): string {
        // Kursbereiche (Kategorien).
        $categories = core_course_category::make_categories_list('', 0, ' / ');
        $kursbereiche = [['value' => '', 'label' => get_string('filter_all', 'block_kursfilter')]];
        foreach ($categories as $id => $name) {
            $kursbereiche[] = ['value' => (string)$id, 'label' => $name];
        }

        // Konfigurierbare Schulformen aus Admin-Settings.
        $schulformRaw = get_config('block_kursfilter', 'schulformen') ?? "Grundschule\nHauptschule\nRealschule\nGymnasium\nGesamtschule\nBerufsschule";
        $schulformen  = array_filter(array_map('trim', explode("\n", $schulformRaw)));

        // Konfigurierbare Fächer.
        $faecherRaw = get_config('block_kursfilter', 'faecher') ?? "Mathematik\nDeutsch\nEnglisch\nNaturwissenschaften\nGeschichte";
        $faecher    = array_filter(array_map('trim', explode("\n", $faecherRaw)));

        // Konfigurierbare Niveaustufen.
        $niveauRaw  = get_config('block_kursfilter', 'niveaustufen') ?? "Klasse 1-4\nKlasse 5-6\nKlasse 7-9\nKlasse 10\nOberstufe";
        $niveaus    = array_filter(array_map('trim', explode("\n", $niveauRaw)));

        $templatedata = [
            'blockid'      => $blockid,
            'kursbereiche' => $kursbereiche,
            'schulformen'  => array_values($schulformen),
            'faecher'      => array_values($faecher),
            'niveaustufen' => array_values($niveaus),
        ];

        return $this->render_from_template('block_kursfilter/block', $templatedata);
    }
}
