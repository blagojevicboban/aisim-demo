<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Public demo site of the AI for VET (AISim) plugins (aisim.tsp.edu.rs).
 *
 * Put this file, aisim-demo.mbz, aisim-demo-sr.mbz, aisim-demo-sk.mbz and aisim-demo-pt.mbz
 * in the Moodle folder, next to config.php (not in public/, so the web cannot run it),
 * and run it from the command line or a cron job:
 *   php demo.php setup   once: site settings, language packs, demo accounts, 4 demo courses
 *   php demo.php reset   every night: demo courses from backup again (posts, attempts and changes are gone)
 *
 * @copyright  2026 Boban Blagojević
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/filterlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

const DEMO_SHORTNAME_EN = 'AISIM-DEMO';
const DEMO_FULLNAME_EN = 'AI for VET: interactive simulations (English)';
const DEMO_MBZ_EN = 'aisim-demo.mbz';

const DEMO_SHORTNAME_SR = 'AISIM-DEMO-SR';
const DEMO_FULLNAME_SR = 'AI za VET: interaktivne simulacije (srpski)';
const DEMO_MBZ_SR = 'aisim-demo-sr.mbz';

const DEMO_SHORTNAME_SK = 'AISIM-DEMO-SK';
const DEMO_FULLNAME_SK = 'AI pre OVP: interaktívne simulácie (slovenčina)';
const DEMO_MBZ_SK = 'aisim-demo-sk.mbz';

const DEMO_SHORTNAME_PT = 'AISIM-DEMO-PT';
const DEMO_FULLNAME_PT = 'IA para EFP: simulações interativas (português)';
const DEMO_MBZ_PT = 'aisim-demo-pt.mbz';

const DEMO_PASSWORD = 'Demo-2026';
const DEMO_USERS = [
    'teacher' => ['firstname' => 'Demo', 'lastname' => 'Teacher', 'role' => 'editingteacher'],
    'student' => ['firstname' => 'Demo', 'lastname' => 'Student', 'role' => 'student'],
];

$command = $argv[1] ?? '';
if (!in_array($command, ['setup', 'reset'], true)) {
    fwrite(STDERR, "Usage: php demo.php setup|reset\n");
    exit(1);
}
\core\session\manager::set_user(get_admin());

// Settings and CSS are always updated so design refinements apply on reset too.
demo_settings();
demo_cleanup_obsolete_courses();

// Language packs: install on setup, or automatically on reset if SK or PT are missing.
demo_languages($command === 'setup');

$en_id = demo_restore_course(DEMO_MBZ_EN, DEMO_SHORTNAME_EN, DEMO_FULLNAME_EN);
$sr_id = demo_restore_course(DEMO_MBZ_SR, DEMO_SHORTNAME_SR, DEMO_FULLNAME_SR);
$sk_id = demo_restore_course(DEMO_MBZ_SK, DEMO_SHORTNAME_SK, DEMO_FULLNAME_SK);
$pt_id = demo_restore_course(DEMO_MBZ_PT, DEMO_SHORTNAME_PT, DEMO_FULLNAME_PT);

$course_map = array_filter([
    'en' => $en_id,
    'sr' => $sr_id,
    'sk' => $sk_id,
    'pt' => $pt_id,
]);

foreach ($course_map as $lang => $cid) {
    demo_section_banner($cid, $lang, $course_map);
    demo_users($cid);
}

if (!empty($course_map)) {
    demo_frontpage($course_map);
}

purge_all_caches();

foreach ($course_map as $lang => $cid) {
    echo "Done [{$lang}]: {$CFG->wwwroot}/course/view.php?id={$cid}\n";
}

/**
 * Removes any leftover cloned courses from CodeLab or Algorithm so only AISim courses remain.
 */
function demo_cleanup_obsolete_courses(): void {
    global $DB;
    $obsolete = ['CODELAB-DEMO', 'CODELAB-DEMO-SR', 'ALGORITHM-DEMO', 'ALGORITHM-DEMO-SR'];
    foreach ($obsolete as $shortname) {
        if ($course = $DB->get_record('course', ['shortname' => $shortname])) {
            delete_course($course, false);
            echo "Deleted obsolete course: {$shortname}\n";
        }
    }
}

/**
 * Site settings of a public demo: no mail, no sign-up, guest button, all content cleaned, the aisim filter on,
 * modern custom CSS for theme_boost.
 */
function demo_settings(): void {
    $settings = [
        'noemailever' => 1,         // Nothing is ever mailed.
        'registerauth' => '',       // No self-registration.
        'auth' => 'manual',
        'guestloginbutton' => 1,    // "Access as a guest" on the login page.
        'forceclean' => 0,          // Keep data-aisim attributes intact for simulation engines.
        'langmenu' => 1,
        'timezone' => 'Europe/Belgrade',
        'maxbytes' => 2097152,
        'messaging' => 0,
        'enableblogs' => 0,
        'enablecourserequests' => 0,
        'defaulthomepage' => 0,
    ];
    foreach ($settings as $name => $value) {
        set_config($name, $value);
    }
    filter_set_global_state('aisim', TEXTFILTER_ON);

    // Demo login instructions on login/index.php
    $auth_instructions = <<<'HTML'
<div class="card border-0 shadow-lg rounded-4 p-4 text-start text-white" style="background: rgba(15, 23, 42, 0.92) !important; backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.18) !important; max-width: 440px; margin: 0 auto;">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="fs-4">⚡</span>
        <h5 class="fw-bold mb-0 text-white">Instant Demo Access</h5>
    </div>
    <div class="p-3 rounded-3 mb-2" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
        <div class="fw-bold mb-1" style="color: #38bdf8;">🧑‍🏫 Teacher: <code>teacher</code></div>
        <div class="small" style="color: rgba(255, 255, 255, 0.9); line-height: 1.4;">Explore all lesson modules, insert AI simulations in TinyMCE, create and configure quiz questions.</div>
    </div>
    <div class="p-3 rounded-3 mb-3" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
        <div class="fw-bold mb-1" style="color: #fbbf24;">🎓 Student: <code>student</code></div>
        <div class="small" style="color: rgba(255, 255, 255, 0.9); line-height: 1.4;">Interact with 9 AI simulation engines, tune hyperparameters, solve autograded quiz challenges.</div>
    </div>
    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small mb-2">
        <span style="color: rgba(255, 255, 255, 0.9);">Password:</span>
        <code style="background: #000000; color: #fde047; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 0.95rem; border: 1px solid rgba(253, 224, 71, 0.4);">Demo-2026</code>
    </div>
    <div class="text-center small mt-2" style="color: rgba(255, 255, 255, 0.75);">
        Or click the <strong>Log in as guest</strong> button below!
    </div>
</div>
HTML;
    set_config('auth_instructions', $auth_instructions);

    // Modern Boost and Moove theme custom CSS with AI/Indigo theme
    $customcss = <<<'CSS'
/* Modern Boost & Moove Theme Enhancements for AISim Demo */
:root {
    --bs-primary: #4f46e5;
    --bs-primary-rgb: 79, 70, 229;
}
body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: #1e293b;
}
.btn-primary {
    background-color: #4f46e5 !important;
    border-color: #4338ca !important;
}
.btn-primary:hover {
    background-color: #4338ca !important;
    border-color: #3730a3 !important;
}
.badge.bg-primary {
    background-color: #4f46e5 !important;
}
.text-primary {
    color: #4f46e5 !important;
}

/* Simulation card container polish */
.aisim-lesson-wrap .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.demo-course-card {
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease;
}
.demo-course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 32px -8px rgba(79, 70, 229, 0.18) !important;
}

/* Auth page card styling */
.login-wrapper {
    background: radial-gradient(circle at top right, #1e1b4b 0%, #0f172a 100%) !important;
    min-height: 100vh;
}
.login-container {
    background: transparent !important;
}
.login-box {
    background: #ffffff !important;
    border-radius: 1.25rem !important;
    box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.25) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    padding: 2.5rem !important;
}

/* Fix Moove theme login split container so instructions display gracefully */
.login-layout-split {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
}

.login-layout-left-content {
    background: transparent !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-width: 480px !important;
    width: 100% !important;
    margin-bottom: 2rem !important;
}

.login-layout-left-content .card {
    background: rgba(15, 23, 42, 0.88) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.18) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
}

.login-layout-left-content .card h5,
.login-layout-left-content .card strong,
.login-layout-left-content .card .fw-bold {
    color: #ffffff !important;
}

.login-layout-left-content .card code {
    background: rgba(0, 0, 0, 0.6) !important;
    color: #fde047 !important;
    padding: 2px 7px !important;
    border-radius: 5px !important;
}

/* Mobile responsive login adjustments */
.login-container .text_to_html .card {
    background: #0f172a !important;
    color: #ffffff !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
}
.login-container .text_to_html .card code {
    background: #000000 !important;
    color: #fde047 !important;
}

/* Login button polish */
.loginform .btn-primary {
    background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%) !important;
    border: none !important;
    padding: 0.75rem 1.5rem !important;
    border-radius: 10px !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4) !important;
}
.loginform .btn-primary:hover {
    background: linear-gradient(135deg, #3730a3 0%, #4338ca 100%) !important;
}
CSS;
    set_config('customcss', $customcss, 'theme_boost');
    set_config('scss', $customcss, 'theme_moove');
    set_config('theme', 'moove');

    // The shared demo accounts cannot change their password, profile or private files.
    $userrole = get_archetype_roles('user');
    $userrole = reset($userrole);
    $system = context_system::instance();
    foreach ([
        'moodle/user:changeownpassword',
        'moodle/user:editownprofile',
        'moodle/user:manageownfiles',
        'moodle/user:deleteownprofile',
    ] as $cap) {
        assign_capability($cap, CAP_PROHIBIT, $userrole->id, $system->id, true);
    }

    demo_ensure_tiny_aisim();
    echo "settings and custom CSS done\n";
}

/**
 * Ensures tiny_aisim is properly deployed from zip and enabled in editor_tiny.
 */
function demo_ensure_tiny_aisim(): void {
    global $CFG;
    $zipcandidates = [
        $CFG->dirroot . '/tiny_aisim-0.10.0.zip',
        __DIR__ . '/tiny_aisim-0.10.0.zip',
    ];
    foreach ($zipcandidates as $zipfile) {
        if (file_exists($zipfile) && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipfile) === true) {
                $dest = $CFG->dirroot . '/lib/editor/tiny/plugins';
                $zip->extractTo($dest);
                $zip->close();
                echo "tiny_aisim-0.10.0.zip deployed into {$dest}/aisim\n";
                break;
            }
        }
    }

    // Ensure tiny_aisim is not in disabledsubplugins
    $disabled = get_config('editor_tiny', 'disabledsubplugins') ?: '';
    if (strpos($disabled, 'aisim') !== false) {
        $parts = array_filter(array_map('trim', explode(',', $disabled)), fn($p) => $p !== 'aisim' && $p !== '');
        set_config('disabledsubplugins', implode(',', $parts), 'editor_tiny');
        echo "tiny_aisim enabled in editor_tiny\n";
    }
}

/**
 * Installs language packs (Serbian Latin/Cyrillic, Slovak, Portuguese).
 */
function demo_languages(bool $force = false): void {
    try {
        $needed = ['sr_lt', 'sr_cr', 'sk', 'pt'];
        $to_install = $needed;
        if (!$force && function_exists('get_string_manager')) {
            $installed = get_string_manager()->get_list_of_translations();
            $to_install = array_values(array_filter($needed, fn($lang) => !isset($installed[$lang])));
        }
        if (!empty($to_install)) {
            $controller = new \tool_langimport\controller();
            $controller->install_languagepacks($to_install);
            echo "installed language packs: " . implode(' ', $to_install) . "\n";
        } else {
            echo "language packs already installed\n";
        }
    } catch (\Throwable $e) {
        echo "language packs warning: " . $e->getMessage() . "\n";
    }
}

/**
 * Restore one course from an MBZ archive.
 */
function demo_restore_course(string $filename, string $shortname, string $fullname): ?int {
    global $DB;
    $mbz = __DIR__ . '/' . $filename;
    if (!is_readable($mbz)) {
        echo "skip {$shortname}: {$mbz} not found\n";
        return null;
    }

    $folder = 'aisimdemo' . preg_replace('/[^a-zA-Z0-9]/', '', $shortname) . time();
    $path = make_backup_temp_directory($folder);
    get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($mbz, $path);
    $admin = get_admin();
    $course = $DB->get_record('course', ['shortname' => $shortname]);
    if ($course) {
        $rc = new restore_controller(
            $folder,
            $course->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $admin->id,
            backup::TARGET_EXISTING_DELETING
        );
        $rc->execute_precheck();
        restore_dbops::delete_course_content($course->id, ['keep_roles_and_enrolments' => 1, 'keep_groups_and_groupings' => 1]);
    } else {
        $category = $DB->get_field('course_categories', 'id', [], IGNORE_MULTIPLE);
        $courseid = restore_dbops::create_new_course($fullname, $shortname, $category);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $rc = new restore_controller(
            $folder,
            $course->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $admin->id,
            backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
    }
    $rc->execute_plan();
    $rc->destroy();
    fulldelete($path);

    $DB->set_field('course', 'fullname', $fullname, ['id' => $course->id]);
    $DB->set_field('course', 'shortname', $shortname, ['id' => $course->id]);
    $DB->set_field('course', 'visible', 1, ['id' => $course->id]);

    echo "course {$shortname} restored ({$course->id})\n";
    return (int)$course->id;
}

/**
 * Section 0 banner with 4-step guided tour, guide button, and 4-language navigation pills.
 */
function demo_section_banner(int $courseid, string $current_lang, array $course_map): void {
    global $CFG, $DB;

    // Locate the Guide page in this course
    $guide_btn = '';
    $guide_page = $DB->get_record_select('page', "course = ? AND (name LIKE ? OR name LIKE ? OR name LIKE ? OR name LIKE ?)", [
        $courseid, '%Guide%', '%Vodič%', '%Príručka%', '%Guia%'
    ]);
    if ($guide_page) {
        $guide_cm = get_coursemodule_from_instance('page', $guide_page->id, $courseid);
        if ($guide_cm) {
            $guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$guide_cm->id}";
            $guide_labels = [
                'en' => '📖 User &amp; Teacher Guide →',
                'sr' => '📖 Vodič za simulacije →',
                'sk' => '📖 Príručka pre simulácie →',
                'pt' => '📖 Guia de simulações →',
            ];
            $btn_text = $guide_labels[$current_lang] ?? '📖 Guide →';
            $guide_btn = '<a href="' . $guide_url . '" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold shadow-sm" style="backdrop-filter: blur(4px);">' . $btn_text . '</a>';
        }
    }

    // Language pills
    $lang_info = [
        'en' => ['flag' => '🇬🇧', 'name' => 'English', 'title' => 'Switch to English course'],
        'sr' => ['flag' => '🇷🇸', 'name' => 'Srpski', 'title' => 'Prebaci na kurs na srpskom'],
        'sk' => ['flag' => '🇸🇰', 'name' => 'Slovenčina', 'title' => 'Prepnúť na kurz v slovenčine'],
        'pt' => ['flag' => '🇵🇹', 'name' => 'Português', 'title' => 'Mudar para o curso em português'],
    ];

    $switch_links = '';
    foreach ($course_map as $l => $cid) {
        if ($l === $current_lang) {
            continue;
        }
        $info = $lang_info[$l] ?? ['flag' => '🌐', 'name' => strtoupper($l), 'title' => ''];
        $url = "{$CFG->wwwroot}/course/view.php?id={$cid}";
        $switch_links .= '<a href="' . $url . '" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold text-white" style="backdrop-filter: blur(4px); border-color: rgba(255,255,255,0.4);" title="' . $info['title'] . '">' . $info['flag'] . ' ' . $info['name'] . '</a> ';
    }

    $banner_data = [
        'en' => [
            'badge' => '✨ AI for VET Demo · English',
            'title' => 'Welcome to AI for VET Simulations!',
            'lead' => 'Experience 9 interactive Machine Learning &amp; AI simulations running directly in your browser. Follow this 4-step tour to test all features:',
            's1' => '1. OPTIMIZATION', 's1_desc' => 'Tune learning rates in Gradient Descent &amp; watch convergence',
            's2' => '2. NEURAL NETS', 's2_desc' => 'Add hidden layers to solve XOR &amp; train in real-time',
            's3' => '3. VISION &amp; RL', 's3_desc' => 'Inspect 3&times;3 convolution kernels &amp; train Q-learning agents',
            's4' => '4. AUTO-GRADED QUIZ', 's4_desc' => 'Solve practical simulation challenges with instant feedback',
            'accounts' => '<div><strong>Demo Accounts:</strong> <code>student</code> or <code>teacher</code> (password: <code>Demo-2026</code>) · or explore as Guest</div>',
        ],
        'sr' => [
            'badge' => '✨ AI for VET Demo · Srpski',
            'title' => 'Dobrodošli u AI for VET simulacije!',
            'lead' => 'Isprobajte 9 interaktivnih simulacija mašinskog učenja koje rade direktno u pregledaču. Upoznajte sistem u 4 koraka:',
            's1' => '1. OPTIMIZACIJA', 's1_desc' => 'Podešavajte stopu učenja gradijentnog spusta i pratite krive',
            's2' => '2. NEURONSKE MREŽE', 's2_desc' => 'Dodajte skrivene slojeve za XOR problem i obučavajte model',
            's3' => '3. RAČUNARSKI VID &amp; RL', 's3_desc' => 'Ispitujte 3&times;3 konvolucione kernele i vodite Q-learning agenta',
            's4' => '4. OCENJIVANI KVIZ', 's4_desc' => 'Rešavajte simulacione zadatke sa automatskim bodovanjem',
            'accounts' => '<div><strong>Demo nalozi:</strong> <code>student</code> ili <code>teacher</code> (lozinka: <code>Demo-2026</code>) · ili pristupite kao Gost</div>',
        ],
        'sk' => [
            'badge' => '✨ AI pre OVP Demo · Slovenčina',
            'title' => 'Vitajte v simuláciách AI pre OVP!',
            'lead' => 'Vyskúšajte 9 interaktívnych simulácií strojového učenia priamo vo vašom prehliadači. Zoznámte sa so systémom v 4 krokoch:',
            's1' => '1. OPTIMALIZÁCIA', 's1_desc' => 'Nastavujte rýchlosť učenia gradientného zostupu a sledujte klesanie',
            's2' => '2. NEURÓNOVÉ SIETE', 's2_desc' => 'Pridávajte skryté vrstvy pre problém XOR a trénujte model',
            's3' => '3. POČÍTAČOVÉ VIDENIE &amp; RL', 's3_desc' => 'Skúmajte 3&times;3 konvolučné jadrá a veďte Q-learning agenta',
            's4' => '4. HODNOTENÝ TEST', 's4_desc' => 'Riešte praktické simulované úlohy s okamžitým hodnotením',
            'accounts' => '<div><strong>Demo účty:</strong> <code>student</code> alebo <code>teacher</code> (heslo: <code>Demo-2026</code>) · alebo vstúpte ako Hosť</div>',
        ],
        'pt' => [
            'badge' => '✨ IA para EFP Demo · Português',
            'title' => 'Bem-vindo às simulações IA para EFP!',
            'lead' => 'Experimente 9 simulações interativas de Machine Learning diretamente no seu navegador. Siga estas 4 etapas para explorar todas as funcionalidades:',
            's1' => '1. OTIMIZAÇÃO', 's1_desc' => 'Ajuste a taxa de aprendizagem na descida do gradiente e veja a convergência',
            's2' => '2. REDES NEURONAIS', 's2_desc' => 'Adicione camadas ocultas para o problema XOR e treine o modelo',
            's3' => '3. VISÃO &amp; RL', 's3_desc' => 'Inspecione kernels de convolução 3&times;3 e treine agentes de Q-learning',
            's4' => '4. TESTE AVALIADO', 's4_desc' => 'Resolva desafios práticos com simulações e avaliação imediata',
            'accounts' => '<div><strong>Contas demo:</strong> <code>student</code> ou <code>teacher</code> (palavra-passe: <code>Demo-2026</code>) · ou explore como Visitante</div>',
        ],
    ];

    $b = $banner_data[$current_lang] ?? $banner_data['en'];

    $banner = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #4338ca 100%); color: #ffffff;">'
        . '<div class="card-body p-4">'
        . '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">'
        . '<div class="d-flex flex-wrap align-items-center gap-2">'
        . '<span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(4px);">' . $b['badge'] . '</span>'
        . $guide_btn
        . '</div>'
        . '<div class="d-flex flex-wrap align-items-center gap-1">' . $switch_links . '</div>'
        . '</div>'
        . '<h2 class="fw-bold text-white mb-2">' . $b['title'] . '</h2>'
        . '<p class="mb-3" style="color: rgba(255,255,255,0.9) !important; font-size: 1.05rem;">' . $b['lead'] . '</p>'
        . '<div class="row g-2 mb-3">'
        . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-warning small mb-1">' . $b['s1'] . '</div><div class="small opacity-90">' . $b['s1_desc'] . '</div></div></div>'
        . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-info small mb-1">' . $b['s2'] . '</div><div class="small opacity-90">' . $b['s2_desc'] . '</div></div></div>'
        . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-success small mb-1">' . $b['s3'] . '</div><div class="small opacity-90">' . $b['s3_desc'] . '</div></div></div>'
        . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-light small mb-1">' . $b['s4'] . '</div><div class="small opacity-90">' . $b['s4_desc'] . '</div></div></div>'
        . '</div>'
        . '<div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small opacity-90">'
        . $b['accounts']
        . '<div>Resetuje se svake noći u 03:30 · Projekat: <a href="https://aiforvet.eu/" target="_blank" class="text-white text-decoration-underline">Erasmus+ AI for VET</a></div>'
        . '</div></div></div>';

    $DB->set_field('course_sections', 'summary', $banner, ['course' => $courseid, 'section' => 0]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => $courseid, 'section' => 0]);
}

/**
 * Enrol test users in the course.
 */
function demo_users(int $courseid): void {
    global $CFG, $DB;
    $manual = enrol_get_plugin('manual');
    $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
    if (!$instance) {
        $manual->add_instance(get_course($courseid));
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], '*', MUST_EXIST);
    }
    foreach (DEMO_USERS as $username => $u) {
        $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
        if (!$user) {
            $id = user_create_user((object) [
                'username' => $username,
                'password' => DEMO_PASSWORD,
                'auth' => 'manual',
                'confirmed' => 1,
                'mnethostid' => $CFG->mnet_localhost_id,
                'firstname' => $u['firstname'],
                'lastname' => $u['lastname'],
                'email' => "{$username}@aisim.tsp.edu.rs",
                'lang' => 'en',
            ], true, false);
            $user = $DB->get_record('user', ['id' => $id], '*', MUST_EXIST);
        } else {
            update_internal_user_password($user, DEMO_PASSWORD);
        }
        $roleid = $DB->get_field('role', 'id', ['shortname' => $u['role']], MUST_EXIST);
        $manual->enrol_user($instance, $user->id, $roleid);
        echo "user {$username} enrolled in course {$courseid} as {$u['role']}\n";
    }
}

/**
 * Front page with live interactive neural network simulation, 4-language cards, and quick login actions.
 */
function demo_frontpage(array $course_map): void {
    global $CFG, $DB;

    $en_id = $course_map['en'] ?? null;
    $sr_id = $course_map['sr'] ?? null;
    $sk_id = $course_map['sk'] ?? null;
    $pt_id = $course_map['pt'] ?? null;

    $en_url = $en_id ? "{$CFG->wwwroot}/course/view.php?id={$en_id}" : '#';
    $sr_url = $sr_id ? "{$CFG->wwwroot}/course/view.php?id={$sr_id}" : '#';
    $sk_url = $sk_id ? "{$CFG->wwwroot}/course/view.php?id={$sk_id}" : '#';
    $pt_url = $pt_id ? "{$CFG->wwwroot}/course/view.php?id={$pt_id}" : '#';

    // Locate English guide
    $en_guide_url = $en_url;
    if ($en_id) {
        $g_en = $DB->get_record_select('page', "course = ? AND name LIKE ?", [$en_id, '%Guide%']);
        if ($g_en) {
            $cm = get_coursemodule_from_instance('page', $g_en->id, $en_id);
            if ($cm) {
                $en_guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$cm->id}";
            }
        }
    }

    $summary = '<p>This is the demo site of the <strong>AI for VET (AISim) plugins for Moodle</strong>: '
        . '<a href="https://github.com/blagojevicboban/moodle-tiny_aisim">tiny_aisim</a> (insert and edit simulations in TinyMCE), '
        . '<a href="https://github.com/blagojevicboban/moodle-filter_aisim">filter_aisim</a> (renders 9 interactive AI simulation engines) and '
        . '<a href="https://github.com/blagojevicboban/moodle-qtype_aisim">qtype_aisim</a> (auto-graded interactive quiz challenges).</p>'
        . '<p>Open the demo courses as a guest, or log in with one of these accounts (password <strong>' . DEMO_PASSWORD . '</strong>):</p><ul>'
        . '<li><strong>teacher</strong>: edit the lesson pages, add/modify simulations in TinyMCE, create and configure quiz questions</li>'
        . '<li><strong>student</strong>: explore all 9 interactive simulations, tune hyperparameters, take the auto-graded quizzes</li></ul>'
        . '<p>Courses available: '
        . '<a href="' . $en_url . '"><strong>🇬🇧 English</strong></a> · '
        . '<a href="' . $sr_url . '"><strong>🇷🇸 Srpski</strong></a> · '
        . '<a href="' . $sk_url . '"><strong>🇸🇰 Slovenčina</strong></a> · '
        . '<a href="' . $pt_url . '"><strong>🇵🇹 Português</strong></a> · '
        . '<a href="' . $en_guide_url . '"><strong>📖 Teacher Guide</strong></a></p>'
        . '<p>Project portal: <a href="https://aiforvet.eu/" target="_blank">aiforvet.eu</a> · Standalone simulations: <a href="https://sim.aiforvet.eu/" target="_blank">sim.aiforvet.eu</a></p>'
        . '<p>Everything you change is reset every night at 03:30. Languages are selectable in the language menu.</p>';

    $DB->update_record('course', (object) [
        'id' => SITEID,
        'fullname' => 'AI for VET Simulations for Moodle: demo',
        'shortname' => 'AISim demo',
        'summary' => $summary,
        'summaryformat' => FORMAT_HTML,
    ]);

    $hero = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #4338ca 100%); color: #ffffff;">'
        . '<div class="card-body p-4 p-md-5">'
        . '<div class="row align-items-center">'
        . '<div class="col-lg-7">'
        . '<div class="d-flex flex-wrap gap-2 mb-3">'
        . '<span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(4px);">✨ Erasmus+ AI for VET</span>'
        . '<span class="badge bg-info bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal">🧠 9 Interactive AI Engines</span>'
        . '<span class="badge bg-warning bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal">⚡ Auto-Graded Quizzes</span>'
        . '<span class="badge bg-light bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal">🌐 4 Languages</span>'
        . '<a href="' . $en_guide_url . '" class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold text-decoration-none shadow-sm">📖 User &amp; Teacher Guide →</a>'
        . '</div>'
        . '<h1 class="display-6 fw-bold text-white mb-3">Interactive AI &amp; Machine Learning Simulations in Moodle</h1>'
        . '<p class="lead mb-4" style="color: rgba(255,255,255,0.92) !important; font-size: 1.15rem;">Bring machine learning to life: gradient descent, confusion matrices, neural networks, computer vision kernels, and reinforcement learning running right inside Moodle lessons and quizzes.</p>'
        . '<div class="d-flex flex-wrap gap-2 mb-2">'
        . ($en_id ? '<a href="' . $en_url . '" class="btn btn-light btn-lg px-3 fw-bold shadow-sm" style="border-radius: 12px; color: #1e1b4b;">🇬🇧 English</a>' : '')
        . ($sr_id ? '<a href="' . $sr_url . '" class="btn btn-outline-light btn-lg px-3 fw-bold shadow-sm" style="border-radius: 12px; border-width: 2px;">🇷🇸 Srpski</a>' : '')
        . ($sk_id ? '<a href="' . $sk_url . '" class="btn btn-outline-light btn-lg px-3 fw-bold shadow-sm" style="border-radius: 12px; border-width: 2px;">🇸🇰 Slovenčina</a>' : '')
        . ($pt_id ? '<a href="' . $pt_url . '" class="btn btn-outline-light btn-lg px-3 fw-bold shadow-sm" style="border-radius: 12px; border-width: 2px;">🇵🇹 Português</a>' : '')
        . '<a href="' . $en_guide_url . '" class="btn btn-warning text-dark btn-lg px-3 fw-bold shadow-sm" style="border-radius: 12px;">📖 Guide</a>'
        . '</div>'
        . '</div>'
        . '<div class="col-lg-5 mt-4 mt-lg-0">'
        . '<div class="p-3 bg-dark bg-opacity-75 rounded-4 shadow-lg border border-white border-opacity-10">'
        . '<div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-white border-opacity-10">'
        . '<div class="small fw-bold text-white"><span class="me-2">▶️</span>Try it live (Gradient Descent Optimizer):</div>'
        . '<span class="badge bg-primary bg-opacity-50 text-white font-monospace small">Live interactive</span>'
        . '</div>'
        . '<div data-aisim="gradient-descent" data-profile="convex" data-lr="0.15"></div>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div></div>'
        // 4-Language Course Cards
        . '<div class="row g-3 mb-4">'
        . ($en_id ? ('<div class="col-md-6 col-lg-3">'
            . '<div class="card h-100 border-0 shadow-sm p-3 demo-course-card d-flex flex-column" style="background:#ffffff; border-top: 4px solid #4f46e5 !important;">'
            . '<div class="d-flex justify-content-between align-items-start mb-2">'
            . '<h5 class="fw-bold mb-0 text-primary">🇬🇧 English</h5>'
            . '<span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill small">AISIM-DEMO</span>'
            . '</div>'
            . '<p class="text-muted small flex-grow-1 mb-3">AI for VET: 9 interactive simulations, autograded quiz challenges, and TinyMCE inserter.</p>'
            . '<a href="' . $en_url . '" class="btn btn-primary btn-sm fw-bold px-3 rounded-3 w-100">🚀 Enter English →</a>'
            . '</div></div>') : '')
        . ($sr_id ? ('<div class="col-md-6 col-lg-3">'
            . '<div class="card h-100 border-0 shadow-sm p-3 demo-course-card d-flex flex-column" style="background:#ffffff; border-top: 4px solid #059669 !important;">'
            . '<div class="d-flex justify-content-between align-items-start mb-2">'
            . '<h5 class="fw-bold mb-0 text-success">🇷🇸 Srpski</h5>'
            . '<span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill small">AISIM-DEMO-SR</span>'
            . '</div>'
            . '<p class="text-muted small flex-grow-1 mb-3">AI za VET: 9 interaktivnih simulacija, ocenjivani kviz zadaci i integracija u Moodle.</p>'
            . '<a href="' . $sr_url . '" class="btn btn-success btn-sm fw-bold px-3 rounded-3 w-100">🇷🇸 Otvori kurs →</a>'
            . '</div></div>') : '')
        . ($sk_id ? ('<div class="col-md-6 col-lg-3">'
            . '<div class="card h-100 border-0 shadow-sm p-3 demo-course-card d-flex flex-column" style="background:#ffffff; border-top: 4px solid #d97706 !important;">'
            . '<div class="d-flex justify-content-between align-items-start mb-2">'
            . '<h5 class="fw-bold mb-0" style="color: #d97706;">🇸🇰 Slovenčina</h5>'
            . '<span class="badge bg-warning bg-opacity-25 text-dark px-2 py-1 rounded-pill small">AISIM-DEMO-SK</span>'
            . '</div>'
            . '<p class="text-muted small flex-grow-1 mb-3">AI pre OVP: 9 interaktívnych simulácií, automaticky hodnotené testy a metodická príručka.</p>'
            . '<a href="' . $sk_url . '" class="btn btn-warning text-dark btn-sm fw-bold px-3 rounded-3 w-100">🇸🇰 Otvoriť kurz →</a>'
            . '</div></div>') : '')
        . ($pt_id ? ('<div class="col-md-6 col-lg-3">'
            . '<div class="card h-100 border-0 shadow-sm p-3 demo-course-card d-flex flex-column" style="background:#ffffff; border-top: 4px solid #0284c7 !important;">'
            . '<div class="d-flex justify-content-between align-items-start mb-2">'
            . '<h5 class="fw-bold mb-0" style="color: #0284c7;">🇵🇹 Português</h5>'
            . '<span class="badge bg-info bg-opacity-10 text-info px-2 py-1 rounded-pill small">AISIM-DEMO-PT</span>'
            . '</div>'
            . '<p class="text-muted small flex-grow-1 mb-3">IA para EFP: 9 simulações interativas, testes avaliados automaticamente e guia pedagógico.</p>'
            . '<a href="' . $pt_url . '" class="btn btn-info text-white btn-sm fw-bold px-3 rounded-3 w-100">🇵🇹 Entrar no curso →</a>'
            . '</div></div>') : '')
        . '</div>'
        // Test Accounts Strip
        . '<div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-light">'
        . '<div class="row g-2 align-items-center text-center text-md-start">'
        . '<div class="col-md-3 fw-bold text-dark"><span class="fs-5 me-2">⚡</span>Instant Demo Access:</div>'
        . '<div class="col-md-3"><div class="p-2 px-3 bg-white rounded-3 shadow-sm border small"><strong>👤 Guest:</strong> Explore all 9 simulations in browser</div></div>'
        . '<div class="col-md-3"><div class="p-2 px-3 bg-white rounded-3 shadow-sm border small"><strong>🎓 Student:</strong> <code>student</code> / <code>Demo-2026</code></div></div>'
        . '<div class="col-md-3"><div class="p-2 px-3 bg-white rounded-3 shadow-sm border small"><strong>🧑‍🏫 Teacher:</strong> <code>teacher</code> / <code>Demo-2026</code></div></div>'
        . '</div></div>'
        // 3 Plugin Cards
        . '<div class="row g-3 mb-4">'
        . '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-3 p-3"><div class="d-flex align-items-center mb-2"><span class="fs-4 me-2">🪄</span><h5 class="card-title mb-0 fw-bold"><a href="https://github.com/blagojevicboban/moodle-tiny_aisim" target="_blank" class="text-decoration-none">tiny_aisim</a></h5></div><p class="card-text text-muted small mb-0">Visual simulation editor plugin for TinyMCE: teachers select from 9 simulations, tune hyperparameters, view a live preview, and insert or double-click edit cards without touching HTML.</p></div></div>'
        . '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-3 p-3"><div class="d-flex align-items-center mb-2"><span class="fs-4 me-2">🧠</span><h5 class="card-title mb-0 fw-bold"><a href="https://github.com/blagojevicboban/moodle-filter_aisim" target="_blank" class="text-decoration-none">filter_aisim</a></h5></div><p class="card-text text-muted small mb-0">High-performance client-side simulation engine rendering 9 interactive ML modules: gradient descent, confusion matrix, polynomial regression, k-NN/trees, k-means, neural nets, convolution, Q-learning, data bias.</p></div></div>'
        . '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-3 p-3"><div class="d-flex align-items-center mb-2"><span class="fs-4 me-2">📋</span><h5 class="card-title mb-0 fw-bold"><a href="https://github.com/blagojevicboban/moodle-qtype_aisim" target="_blank" class="text-decoration-none">qtype_aisim</a></h5></div><p class="card-text text-muted small mb-0">Interactive question type for Moodle Quizzes: students manipulate live simulations during quiz attempts, and Moodle automatically grades algorithmic convergence against target tolerances.</p></div></div>'
        . '</div>';

    $DB->set_field('course_sections', 'summary', $hero, ['course' => SITEID, 'section' => 1]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => SITEID, 'section' => 1]);
    set_config('numsections', 1);
    set_config('frontpage', FRONTPAGEALLCOURSELIST);
    set_config('frontpageloggedin', FRONTPAGEALLCOURSELIST);
    echo "front page done\n";
}
