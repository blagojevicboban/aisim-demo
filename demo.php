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
 * Put this file, aisim-demo.mbz and aisim-demo-sr.mbz in the Moodle folder, next to config.php (not in public/,
 * so the web cannot run it), and run it from the command line or a cron job:
 *   php demo.php setup   once: site settings, language packs, demo accounts, both demo courses
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
if ($command === 'setup') {
    demo_languages();
}

$en_id = demo_restore_course(DEMO_MBZ_EN, DEMO_SHORTNAME_EN, DEMO_FULLNAME_EN);
$sr_id = demo_restore_course(DEMO_MBZ_SR, DEMO_SHORTNAME_SR, DEMO_FULLNAME_SR);

if ($en_id && $sr_id) {
    demo_section_banner($en_id, 'en', $sr_id);
    demo_section_banner($sr_id, 'sr', $en_id);
} else if ($en_id) {
    demo_section_banner_single($en_id);
} else if ($sr_id) {
    demo_section_banner_single($sr_id);
}

if ($en_id) {
    demo_users($en_id);
}
if ($sr_id) {
    demo_users($sr_id);
}

if ($en_id && $sr_id) {
    demo_frontpage($en_id, $sr_id);
} else {
    demo_frontpage_single($en_id ?: $sr_id);
}

purge_all_caches();
if ($en_id) {
    echo "Done English: {$CFG->wwwroot}/course/view.php?id={$en_id}\n";
}
if ($sr_id) {
    echo "Done Serbian: {$CFG->wwwroot}/course/view.php?id={$sr_id}\n";
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
        'registerauth' => '',       // No self registration.
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
<div class="card border-0 shadow-lg rounded-4 p-4 text-start text-white" style="background: rgba(15, 23, 42, 0.9) !important; backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.2) !important;">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="fs-4">⚡</span>
        <h5 class="fw-bold mb-0 text-white">Instant Demo Access</h5>
    </div>
    <div class="p-3 rounded-3 mb-2" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
        <div class="fw-bold mb-1" style="color: #38bdf8;">🧑‍🏫 Teacher: <code>teacher</code></div>
        <div class="small" style="color: rgba(255, 255, 255, 0.85);">Explore all lesson modules, insert AI simulations in TinyMCE, create and configure quiz questions.</div>
    </div>
    <div class="p-3 rounded-3 mb-3" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
        <div class="fw-bold mb-1" style="color: #fbbf24;">🎓 Student: <code>student</code></div>
        <div class="small" style="color: rgba(255, 255, 255, 0.85);">Interact with 9 AI simulation engines, tune hyperparameters, solve autograded quiz challenges.</div>
    </div>
    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small mb-2">
        <span style="color: rgba(255, 255, 255, 0.85);">Password:</span>
        <code style="background: #000000; color: #fde047; padding: 3px 8px; border-radius: 6px; font-weight: bold; font-size: 0.95rem;">Demo-2026</code>
    </div>
    <div class="text-center small mt-2" style="color: rgba(255, 255, 255, 0.7);">
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
.card {
    border-radius: 14px;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}
.demo-course-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.demo-course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 30px rgba(0,0,0,0.12) !important;
}
.badge {
    font-weight: 600;
    letter-spacing: 0.02em;
}
pre, code, kbd, samp {
    font-family: 'Fira Code', Consolas, 'Courier New', monospace;
}
.navbar-bootswatch, .navbar-light {
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
#page-footer {
    background: #090d16;
    color: #94a3b8;
    padding-top: 2rem;
    padding-bottom: 2rem;
}
#page-footer a {
    color: #818cf8;
}
.aisim-container {
    border-radius: 12px;
}

/* AI-themed Neural Network background for login page */
.login-layout-left {
    background-color: #090d16 !important;
    background-image: 
        radial-gradient(at 15% 15%, rgba(99, 102, 241, 0.45) 0px, transparent 50%),
        radial-gradient(at 85% 25%, rgba(56, 189, 248, 0.4) 0px, transparent 45%),
        radial-gradient(at 50% 85%, rgba(67, 56, 202, 0.5) 0px, transparent 60%),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='600' viewBox='0 0 800 800'%3E%3Cg fill='none' stroke='rgba(99,102,241,0.22)' stroke-width='1.5'%3E%3Cpath d='M100 200 L250 120 L400 220 L550 140 L700 240 M250 120 L400 350 L250 500 M400 220 L550 380 L400 550 M550 140 L700 360 L550 520 M100 450 L250 500 L400 550 L550 520 L700 600 M250 500 L400 680 L550 520'/%3E%3Ccircle cx='100' cy='200' r='6' fill='%2338bdf8' opacity='0.7'/%3E%3Ccircle cx='250' cy='120' r='8' fill='%23818cf8' opacity='0.8'/%3E%3Ccircle cx='400' cy='220' r='7' fill='%236366f1' opacity='0.8'/%3E%3Ccircle cx='550' cy='140' r='8' fill='%2338bdf8' opacity='0.8'/%3E%3Ccircle cx='700' cy='240' r='6' fill='%23818cf8' opacity='0.7'/%3E%3Ccircle cx='400' cy='350' r='9' fill='%23a855f7' opacity='0.8'/%3E%3Ccircle cx='550' cy='380' r='7' fill='%236366f1' opacity='0.8'/%3E%3Ccircle cx='700' cy='360' r='6' fill='%2338bdf8' opacity='0.7'/%3E%3Ccircle cx='100' cy='450' r='6' fill='%23818cf8' opacity='0.7'/%3E%3Ccircle cx='250' cy='500' r='8' fill='%236366f1' opacity='0.8'/%3E%3Ccircle cx='400' cy='550' r='9' fill='%23818cf8' opacity='0.8'/%3E%3Ccircle cx='550' cy='520' r='8' fill='%2338bdf8' opacity='0.8'/%3E%3Ccircle cx='700' cy='600' r='6' fill='%23a855f7' opacity='0.7'/%3E%3Ccircle cx='400' cy='680' r='7' fill='%236366f1' opacity='0.8'/%3E%3C/g%3E%3C/svg%3E"),
        linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e1b4b 100%) !important;
    background-size: cover !important;
    background-position: center !important;
    position: relative !important;
}

/* Remove default watermark */
.login-layout-left::after {
    display: none !important;
}

/* Clean transparent content holder */
.login-layout-left-content {
    background: transparent !important;
    border: none !important;
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
    set_config('customcss', $customcss, 'theme_moove');

    // The shared demo accounts cannot change their password, profile or private files.
    $userrole = get_archetype_roles('user');
    $userrole = reset($userrole);
    $system = context_system::instance();
    foreach (['moodle/user:changeownpassword', 'moodle/user:editownprofile', 'moodle/user:manageownfiles'] as $cap) {
        assign_capability($cap, CAP_PROHIBIT, $userrole->id, $system->id, true);
    }
    // Set native theme_moove login background image
    demo_set_login_background();
    echo "settings and custom CSS done\n";
}

/**
 * Sets the theme_moove login background image natively in Moodle File Storage
 * and directly on disk to completely replace the default office photo.
 */
function demo_set_login_background(): void {
    global $CFG;
    $bgfile = __DIR__ . '/login_bg.png';
    if (!file_exists($bgfile)) {
        echo "login_bg.png not found\n";
        return;
    }

    try {
        $fs = get_file_storage();
        $syscontext = \context_system::instance();
        $filerecord = [
            'contextid' => $syscontext->id,
            'component' => 'theme_moove',
            'filearea' => 'loginbgimg',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'login_bg.png',
        ];
        $fs->delete_area_files($syscontext->id, 'theme_moove', 'loginbgimg');
        $fs->create_file_from_pathname($filerecord, $bgfile);
        set_config('loginbgimg', 'login_bg.png', 'theme_moove');
        echo "theme_moove loginbgimg set in file storage\n";
    } catch (\Throwable $e) {
        echo "Could not set loginbgimg in file storage: " . $e->getMessage() . "\n";
    }

    // Also overwrite default theme pix files on disk if present
    $pix_candidates = [
        $CFG->dirroot . '/theme/moove/pix/login_background.jpg',
        $CFG->dirroot . '/theme/moove/pix/login_background.png',
    ];
    foreach ($pix_candidates as $pix) {
        if (file_exists($pix)) {
            @copy($bgfile, $pix);
            echo "Replaced default pix on disk: {$pix}\n";
        }
    }
}

/**
 * Serbian language packs (Latin and Cyrillic).
 */
function demo_languages(): void {
    try {
        $controller = new \tool_langimport\controller();
        $controller->install_languagepacks(['sr_lt', 'sr_cr']);
        echo "language packs: " . implode(' ', $controller->info) . "\n";
    } catch (\Throwable $e) {
        echo "language packs not installed: " . $e->getMessage() . "\n";
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
 * Section 0 banner with 4-step guided tour, guide button, and language switcher button.
 */
function demo_section_banner(int $courseid, string $lang, int $other_courseid): void {
    global $CFG, $DB;
    $other_url = "{$CFG->wwwroot}/course/view.php?id={$other_courseid}";

    // Locate the Guide page in this course
    $guide_btn = '';
    $guide_page = $DB->get_record_select('page', "course = ? AND (name LIKE ? OR name LIKE ?)", [$courseid, '%Guide%', '%Vodič%']);
    if ($guide_page) {
        $guide_cm = get_coursemodule_from_instance('page', $guide_page->id, $courseid);
        if ($guide_cm) {
            $guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$guide_cm->id}";
            $btn_text = ($lang === 'en') ? '📖 User &amp; Teacher Guide →' : '📖 Vodič za simulacije →';
            $guide_btn = '<a href="' . $guide_url . '" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold shadow-sm" style="backdrop-filter: blur(4px);">' . $btn_text . '</a>';
        }
    }

    if ($lang === 'en') {
        $banner = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #4338ca 100%); color: #ffffff;">'
            . '<div class="card-body p-4">'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">'
            . '<div class="d-flex flex-wrap align-items-center gap-2">'
            . '<span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(4px);">✨ AI for VET Demo · English</span>'
            . $guide_btn
            . '</div>'
            . '<a href="' . $other_url . '" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold" style="backdrop-filter: blur(4px);">🇷🇸 Prebaci na srpski kurs →</a>'
            . '</div>'
            . '<h2 class="fw-bold text-white mb-2">Welcome to AI for VET Simulations!</h2>'
            . '<p class="mb-3" style="color: rgba(255,255,255,0.9) !important; font-size: 1.05rem;">Experience 9 interactive Machine Learning &amp; AI simulations running directly in your browser. Follow this 4-step tour to test all features:</p>'
            . '<div class="row g-2 mb-3">'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-warning small mb-1">1. OPTIMIZATION</div><div class="small opacity-90">Tune learning rates in Gradient Descent &amp; watch convergence</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-info small mb-1">2. NEURAL NETS</div><div class="small opacity-90">Add hidden layers to solve XOR &amp; train in real-time</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-success small mb-1">3. VISION &amp; RL</div><div class="small opacity-90">Inspect 3&times;3 convolution kernels &amp; train Q-learning agents</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-light small mb-1">4. AUTO-GRADED QUIZ</div><div class="small opacity-90">Solve practical simulation challenges with instant feedback</div></div></div>'
            . '</div>'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small opacity-90">'
            . '<div><strong>Demo Accounts:</strong> <code>student</code> or <code>teacher</code> (password: <code>Demo-2026</code>) · or explore as Guest</div>'
            . '<div>Resets nightly at 03:30 · Project: <a href="https://aiforvet.eu/" target="_blank" class="text-white text-decoration-underline">Erasmus+ AI for VET</a></div>'
            . '</div></div></div>';
    } else {
        $banner = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #4338ca 100%); color: #ffffff;">'
            . '<div class="card-body p-4">'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">'
            . '<div class="d-flex flex-wrap align-items-center gap-2">'
            . '<span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(4px);">✨ AI for VET Demo · Srpski</span>'
            . $guide_btn
            . '</div>'
            . '<a href="' . $other_url . '" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold" style="backdrop-filter: blur(4px);">🌐 Switch to English Course →</a>'
            . '</div>'
            . '<h2 class="fw-bold text-white mb-2">Dobrodošli u AI for VET simulacije!</h2>'
            . '<p class="mb-3" style="color: rgba(255,255,255,0.9) !important; font-size: 1.05rem;">Isprobajte 9 interaktivnih simulacija mašinskog učenja koje rade direktno u pregledaču. Upoznajte sistem u 4 koraka:</p>'
            . '<div class="row g-2 mb-3">'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-warning small mb-1">1. OPTIMIZACIJA</div><div class="small opacity-90">Podešavajte stopu učenja gradijentnog spusta i pratite krive</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-info small mb-1">2. NEURONSKE MREŽE</div><div class="small opacity-90">Dodajte skrivene slojeve za XOR problem i obučavajte model</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-success small mb-1">3. RAČUNARSKI VID &amp; RL</div><div class="small opacity-90">Ispitujte 3&times;3 konvolucione kernele i vodite Q-learning agenta</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-light small mb-1">4. OCENJIVANI KVIZ</div><div class="small opacity-90">Rešavajte simulacione zadatke sa automatskim bodovanjem</div></div></div>'
            . '</div>'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small opacity-90">'
            . '<div><strong>Demo nalozi:</strong> <code>student</code> ili <code>teacher</code> (lozinka: <code>Demo-2026</code>) · ili pristupite kao Gost</div>'
            . '<div>Resetuje se svake noći u 03:30 · Projekat: <a href="https://aiforvet.eu/" target="_blank" class="text-white text-decoration-underline">Erasmus+ AI for VET</a></div>'
            . '</div></div></div>';
    }
    $DB->set_field('course_sections', 'summary', $banner, ['course' => $courseid, 'section' => 0]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => $courseid, 'section' => 0]);
}

/**
 * Fallback section banner when only one course exists.
 */
function demo_section_banner_single(int $courseid): void {
    global $DB;
    $banner = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #4338ca 100%); color: #ffffff;">'
        . '<div class="card-body p-4">'
        . '<h2 class="fw-bold text-white mb-2">Welcome to AI for VET Simulations!</h2>'
        . '<p class="mb-3 text-white-50">Explore 9 interactive simulations across gradient descent, confusion matrices, neural networks, computer vision, and reinforcement learning.</p>'
        . '</div></div>';
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
 * Front page with live interactive neural network simulation, dual-language cards, and quick login actions.
 */
function demo_frontpage(int $en_id, int $sr_id): void {
    global $CFG, $DB;
    $en_url = "{$CFG->wwwroot}/course/view.php?id={$en_id}";
    $sr_url = "{$CFG->wwwroot}/course/view.php?id={$sr_id}";

    // Locate guides
    $en_guide_url = "{$CFG->wwwroot}/course/view.php?id={$en_id}";
    $sr_guide_url = "{$CFG->wwwroot}/course/view.php?id={$sr_id}";
    $g_en = $DB->get_record_select('page', "course = ? AND name LIKE ?", [$en_id, '%Guide%']);
    if ($g_en) {
        $cm = get_coursemodule_from_instance('page', $g_en->id, $en_id);
        if ($cm) {
            $en_guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$cm->id}";
        }
    }
    $g_sr = $DB->get_record_select('page', "course = ? AND name LIKE ?", [$sr_id, '%Vodič%']);
    if ($g_sr) {
        $cm = get_coursemodule_from_instance('page', $g_sr->id, $sr_id);
        if ($cm) {
            $sr_guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$cm->id}";
        }
    }

    $summary = '<p>This is the demo site of the <strong>AI for VET (AISim) plugins for Moodle</strong>: '
        . '<a href="https://github.com/blagojevicboban/moodle-tiny_aisim">tiny_aisim</a> (insert and edit simulations in TinyMCE), '
        . '<a href="https://github.com/blagojevicboban/moodle-filter_aisim">filter_aisim</a> (renders 9 interactive AI simulation engines) and '
        . '<a href="https://github.com/blagojevicboban/moodle-qtype_aisim">qtype_aisim</a> (auto-graded interactive quiz challenges).</p>'
        . '<p>Open the demo courses as a guest, or log in with one of these accounts (password <strong>' . DEMO_PASSWORD . '</strong>):</p><ul>'
        . '<li><strong>teacher</strong>: edit the lesson pages, add/modify simulations in TinyMCE, create and configure quiz questions</li>'
        . '<li><strong>student</strong>: explore all 9 interactive simulations, tune hyperparameters, take the auto-graded quizzes</li></ul>'
        . '<p>Courses available: <a href="' . $en_url . '"><strong>AI for VET: interactive simulations (English)</strong></a> · <a href="' . $sr_url . '"><strong>AI za VET: interaktivne simulacije (srpski)</strong></a> · <a href="' . $en_guide_url . '"><strong>📖 User &amp; Teacher Guide</strong></a></p>'
        . '<p>Project portal: <a href="https://aiforvet.eu/" target="_blank">aiforvet.eu</a> · Standalone simulations: <a href="https://sim.aiforvet.eu/" target="_blank">sim.aiforvet.eu</a></p>'
        . '<p>Everything you change is reset every night at 03:30. Serbian (Latin and Cyrillic) is in the language menu.</p>';

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
        . '<a href="' . $en_guide_url . '" class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold text-decoration-none shadow-sm">📖 User &amp; Teacher Guide →</a>'
        . '</div>'
        . '<h1 class="display-6 fw-bold text-white mb-3">Interactive AI &amp; Machine Learning Simulations in Moodle</h1>'
        . '<p class="lead mb-4" style="color: rgba(255,255,255,0.92) !important; font-size: 1.15rem;">Bring machine learning to life: gradient descent, confusion matrices, neural networks, computer vision kernels, and reinforcement learning running right inside Moodle lessons and quizzes.</p>'
        . '<div class="d-flex flex-wrap gap-3 mb-2">'
        . '<a href="' . $en_url . '" class="btn btn-light btn-lg px-4 fw-bold shadow-sm" style="border-radius: 12px; color: #1e1b4b;">🇬🇧 Open English Demo</a>'
        . '<a href="' . $sr_url . '" class="btn btn-outline-light btn-lg px-4 fw-bold shadow-sm" style="border-radius: 12px; border-width: 2px;">🇷🇸 Otvori kurs na srpskom</a>'
        . '<a href="' . $en_guide_url . '" class="btn btn-warning text-dark btn-lg px-4 fw-bold shadow-sm" style="border-radius: 12px;">📖 User &amp; Teacher Guide</a>'
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
        // Dual-Language Cards
        . '<div class="row g-3 mb-4">'
        . '<div class="col-md-6">'
        . '<div class="card h-100 border-0 shadow-sm p-4 demo-course-card" style="background:#ffffff; border-left: 5px solid #4f46e5 !important;">'
        . '<div class="d-flex justify-content-between align-items-start mb-2">'
        . '<h4 class="fw-bold mb-0 text-primary">🇬🇧 AI for VET: Demo (English)</h4>'
        . '<span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">AISIM-DEMO</span>'
        . '</div>'
        . '<p class="text-muted mb-3">Explore 9 comprehensive Machine Learning modules: optimization, ROC/confusion matrix, polynomial regression, k-NN vs trees, k-means, perceptron/neural networks, convolution, Q-learning, and autograded quizzes.</p>'
        . '<a href="' . $en_url . '" class="btn btn-primary fw-bold px-4 rounded-3 align-self-start">🚀 Enter English Course →</a>'
        . '</div></div>'
        . '<div class="col-md-6">'
        . '<div class="card h-100 border-0 shadow-sm p-4 demo-course-card" style="background:#ffffff; border-left: 5px solid #059669 !important;">'
        . '<div class="d-flex justify-content-between align-items-start mb-2">'
        . '<h4 class="fw-bold mb-0 text-success">🇷🇸 AI za VET: Demo (srpski)</h4>'
        . '<span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill">AISIM-DEMO-SR</span>'
        . '</div>'
        . '<p class="text-muted mb-3">Kompletan kurs sa 9 interaktivnih simulacija: gradijentni spust, matrica konfuzije, nelinearne granice, k-means, obučavanje neuronskih mreža za XOR, filtri slike, pojačano učenje i interaktivni kviz.</p>'
        . '<a href="' . $sr_url . '" class="btn btn-success fw-bold px-4 rounded-3 align-self-start">🇷🇸 Otvori kurs na srpskom →</a>'
        . '</div></div>'
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

/**
 * Front page with a single course fallback.
 */
function demo_frontpage_single(int $courseid): void {
    global $CFG, $DB;
    $url = "{$CFG->wwwroot}/course/view.php?id={$courseid}";
    $hero = '<div class="card border-0 shadow-sm rounded-4 mb-4 p-5 text-white" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 100%);">'
        . '<h1 class="display-6 fw-bold mb-3">AI for VET Simulations Demo</h1>'
        . '<a href="' . $url . '" class="btn btn-light btn-lg px-4 fw-bold">Open Course</a>'
        . '</div>';
    $DB->set_field('course_sections', 'summary', $hero, ['course' => SITEID, 'section' => 1]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => SITEID, 'section' => 1]);
}
