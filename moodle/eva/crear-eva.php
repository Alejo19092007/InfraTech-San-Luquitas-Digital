<?php
// ============================================================================
//  Crea el EVA "San Luquitas Digital" por código (se ejecuta una sola vez):
//   - identidad visual (nombre, color, logo)
//   - usuarios ficticios: admin (ya existe), docente.martha, est.ana, est.juan, est.sofia
//   - curso "Mis primeros pasos en programación": 3 unidades, recursos, tarea,
//     cuestionario y foro, con los estudiantes y la docente matriculados
//  Es idempotente: si algo ya existe, lo omite. Cada paso informa OK o FALLO y un
//  fallo no detiene los siguientes. Uso: php /opt/eva/crear-eva.php
// ============================================================================
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/lib/resourcelib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/lib/questionlib.php');

$fallos = 0;
function paso(string $nombre, callable $f) {
    global $fallos;
    try {
        $r = $f();
        echo "  [OK]    $nombre" . ($r ? " — $r" : '') . "\n";
    } catch (Throwable $e) {
        $fallos++;
        echo "  [FALLO] $nombre — " . $e->getMessage() . "\n";
    }
}

$clave = getenv('MOODLE_USERS_PASSWORD') ?: 'SanLuquitas#2026';
$admin = get_admin();
\core\session\manager::set_user($admin);     // todo se ejecuta como administrador

echo ">> Identidad visual\n";
paso('Idioma español (paquete de idioma)', function () use ($CFG) {
    $c = new \tool_langimport\controller();
    $c->install_languagepacks(['es']);
    set_config('lang', 'es');
    return 'idioma por defecto: es';
});
paso('Nombre y descripción del sitio', function () use ($DB) {
    $DB->update_record('course', (object) [
        'id' => SITEID,
        'fullname' => 'San Luquitas Digital',
        'shortname' => 'SLD',
        'summary' => '<p>Aula virtual de la Institución Educativa San Luquitas. Aquí los estudiantes de 4.º y 5.º aprenden a programar a su ritmo.</p>',
        'summaryformat' => FORMAT_HTML,
    ]);
});
paso('Color institucional (tema Boost)', function () {
    set_config('brandcolor', '#0b3d7a', 'theme_boost');
});
paso('Logo con el escudo', function () {
    $ruta = '/opt/eva/escudo.png';
    if (!is_readable($ruta)) throw new Exception("no existe $ruta");
    $ctx = context_system::instance();
    $fs = get_file_storage();
    $fs->delete_area_files($ctx->id, 'core_admin', 'logo');
    $fs->create_file_from_pathname([
        'contextid' => $ctx->id, 'component' => 'core_admin', 'filearea' => 'logo',
        'itemid' => 0, 'filepath' => '/', 'filename' => 'escudo.png',
    ], $ruta);
    set_config('logo', '/escudo.png', 'core_admin');
});
paso('Zona horaria y país', function () {
    set_config('timezone', 'America/Bogota');
    set_config('country', 'CO');
});

echo ">> Usuarios ficticios\n";
$usuarios = [
    ['docente.martha', 'Martha', 'Rojas', 'docente.martha@sanluquitas.local'],
    ['est.ana', 'Ana', 'Gómez', 'est.ana@sanluquitas.local'],
    ['est.juan', 'Juan', 'Pérez', 'est.juan@sanluquitas.local'],
    ['est.sofia', 'Sofía', 'Ramírez', 'est.sofia@sanluquitas.local'],
];
$ids = [];
foreach ($usuarios as [$login, $nombre, $apellido, $correo]) {
    paso("Usuario $login", function () use ($login, $nombre, $apellido, $correo, $clave, $CFG, $DB, &$ids) {
        if ($u = $DB->get_record('user', ['username' => $login, 'mnethostid' => $CFG->mnet_localhost_id])) {
            $ids[$login] = $u->id;
            return 'ya existía';
        }
        $ids[$login] = user_create_user((object) [
            'username' => $login, 'password' => $clave, 'auth' => 'manual', 'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id, 'firstname' => $nombre, 'lastname' => $apellido,
            'email' => $correo, 'lang' => 'es', 'timezone' => 'America/Bogota', 'country' => 'CO',
        ], true, false);
        return 'creado';
    });
}

echo ">> Curso\n";
$curso = null;
paso('Curso "Mis primeros pasos en programación"', function () use (&$curso, $DB) {
    if ($curso = $DB->get_record('course', ['shortname' => 'PROG-4-5'])) return 'ya existía';
    $curso = create_course((object) [
        'fullname' => 'Mis primeros pasos en programación',
        'shortname' => 'PROG-4-5',
        'category' => $DB->get_field_select('course_categories', 'MIN(id)', 'parent = 0'),
        'idnumber' => '', 'newsitems' => 1, 'numsections' => 3, 'startdate' => usergetmidnight(time()),
        'format' => 'topics',
        'visible' => 1,
        'lang' => '',
        'summaryformat' => FORMAT_HTML,
        'summary' => '<p><strong>Para estudiantes de 4.º y 5.º de primaria.</strong> Aprenderás a pensar como un programador: encontrar patrones, escribir instrucciones claras y crear tus propios juegos y animaciones con Scratch.</p>'
            . '<p><strong>Al terminar el curso podrás:</strong></p><ul>'
            . '<li>Reconocer secuencias y patrones en la vida diaria.</li>'
            . '<li>Escribir algoritmos sencillos paso a paso.</li>'
            . '<li>Crear una animación corta en Scratch.</li></ul>',
    ]);
    return 'creado';
});
if (!$curso) { echo "No se pudo crear el curso; se omite el resto.\n"; exit(1); }

paso('Unidades (secciones)', function () use ($curso, $DB) {
    course_create_sections_if_missing($curso, [1, 2, 3]);
    $nombres = [
        0 => 'Bienvenida',
        1 => 'Unidad 1: Pensamiento computacional — secuencias y patrones',
        2 => 'Unidad 2: Algoritmos cotidianos — instrucciones y resolución de problemas',
        3 => 'Unidad 3: Programación visual — primeros pasos con Scratch',
    ];
    foreach ($nombres as $n => $nombre) {
        $DB->set_field('course_sections', 'name', $nombre, ['course' => $curso->id, 'section' => $n]);
    }
    rebuild_course_cache($curso->id, true);
});

/** Crea una actividad con los valores por defecto que usa el generador oficial de Moodle. */
function actividad(object $curso, string $modulo, int $seccion, array $datos): object {
    global $DB;
    $info = (object) ($datos + [
        'modulename' => $modulo,
        'module' => $DB->get_field('modules', 'id', ['name' => $modulo], MUST_EXIST),
        'course' => $curso->id, 'section' => $seccion,
        'visible' => 1, 'visibleoncoursepage' => 1, 'cmidnumber' => '', 'groupmode' => 0, 'groupingid' => 0,
        'availability' => null, 'completion' => 0, 'completionview' => 0, 'completionexpected' => 0,
        'completionpassgrade' => 0, 'conditiongradegroup' => [], 'conditionfieldgroup' => [],
        'conditioncompletiongroup' => [],
    ]);
    if (!isset($info->intro)) { $info->intro = ''; }
    $info->introformat = FORMAT_HTML;
    return add_moduleinfo($info, get_course($curso->id));
}
function existe(object $curso, string $modulo, string $nombre): bool {
    global $DB;
    return $DB->record_exists($modulo, ['course' => $curso->id, 'name' => $nombre]);
}
function pagina(object $curso, int $sec, string $nombre, string $html): void {
    if (existe($curso, 'page', $nombre)) return;
    actividad($curso, 'page', $sec, [
        'name' => $nombre, 'content' => $html, 'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_AUTO, 'printintro' => 0, 'printlastmodified' => 1,
    ]);
}

echo ">> Recursos y actividades\n";
paso('Presentación del curso (página)', fn() => pagina($curso, 0, 'Presentación del curso',
    '<h3>¡Bienvenido a Mis primeros pasos en programación!</h3>'
  . '<p>Este curso es para estudiantes de <strong>4.º y 5.º</strong>. Tiene tres unidades. En cada una verás una explicación corta, harás una actividad y al final resolverás un cuestionario.</p>'
  . '<p><strong>Qué vas a aprender</strong></p><ul><li>A encontrar patrones y ordenar secuencias.</li><li>A escribir instrucciones claras (algoritmos).</li><li>A crear una animación en Scratch.</li></ul>'
  . '<p>Si tienes dudas, escríbelas en el foro: tu docente o tus compañeros te ayudan.</p>'));

paso('Foro de preguntas', function () use ($curso) {
    if (existe($curso, 'forum', 'Foro de preguntas y compañeros')) return 'ya existía';
    actividad($curso, 'forum', 0, [
        'name' => 'Foro de preguntas y compañeros', 'type' => 'general', 'assessed' => 0, 'scale' => 0,
        'forcesubscribe' => 0, 'grade_forum' => 0,
        'intro' => '<p>Pregunta lo que no entiendas o cuéntanos qué proyecto estás haciendo. Escribe con respeto.</p>',
    ]);
});

paso('Unidad 1 — ¿Qué es el pensamiento computacional? (página)', fn() => pagina($curso, 1, '¿Qué es el pensamiento computacional?',
    '<p>Pensar como un programador es resolver problemas por partes. Tiene tres ideas:</p>'
  . '<ul><li><strong>Secuencia:</strong> hacer las cosas en orden. Para lavarte los dientes: abrir la crema, poner crema al cepillo, cepillar, enjuagar.</li>'
  . '<li><strong>Patrón:</strong> algo que se repite. En 2, 4, 6, 8… cada número es el anterior más 2.</li>'
  . '<li><strong>Descomponer:</strong> dividir un problema grande en pasos pequeños.</li></ul>'
  . '<p><strong>Actividad en tu cuaderno:</strong> escribe en orden los pasos para preparar un jugo de naranja y busca un patrón en los números de tu salón.</p>'));

paso('Unidad 1 — CS Unplugged (enlace)', function () use ($curso) {
    if (existe($curso, 'url', 'Juegos sin computador (CS Unplugged)')) return 'ya existía';
    actividad($curso, 'url', 1, [
        'name' => 'Juegos sin computador (CS Unplugged)', 'externalurl' => 'https://csunplugged.org/es/',
        'display' => RESOURCELIB_DISPLAY_AUTO, 'printintro' => 0,
        'intro' => '<p>Actividades para aprender computación sin usar el computador.</p>',
    ]);
});

paso('Unidad 2 — Algoritmos cotidianos (página)', fn() => pagina($curso, 2, 'Algoritmos cotidianos',
    '<p>Un <strong>algoritmo</strong> es una lista de pasos, claros y en orden, para lograr algo. Una receta es un algoritmo.</p>'
  . '<p><strong>Ejemplo:</strong> cómo llegar de tu salón al baño.</p><ol><li>Sal por la puerta.</li><li>Gira a la derecha.</li><li>Camina hasta el final del pasillo.</li><li>Entra por la puerta azul.</li></ol>'
  . '<p>Si un compañero sigue tus pasos y llega, ¡tu algoritmo funciona! Si se pierde, revisa qué paso faltó.</p>'));

paso('Unidad 2 — Tarea: mi algoritmo', function () use ($curso) {
    if (existe($curso, 'assign', 'Tarea: escribe tu algoritmo')) return 'ya existía';
    actividad($curso, 'assign', 2, [
        'name' => 'Tarea: escribe tu algoritmo',
        'intro' => '<p>Escribe los pasos, en orden, para <strong>hacer un sándwich</strong> (o la actividad que prefieras). Mínimo 6 pasos. Súbelo como texto.</p>',
        'alwaysshowdescription' => 1, 'submissiondrafts' => 0, 'requiresubmissionstatement' => 0,
        'sendnotifications' => 0, 'sendstudentnotifications' => 0, 'sendlatenotifications' => 0,
        'duedate' => 0, 'allowsubmissionsfromdate' => 0, 'grade' => 100, 'cutoffdate' => 0, 'gradingduedate' => 0,
        'teamsubmission' => 0, 'requireallteammemberssubmit' => 0, 'teamsubmissiongroupingid' => 0,
        'blindmarking' => 0, 'attemptreopenmethod' => 'none', 'maxattempts' => 1, 'markingworkflow' => 0,
        'markingallocation' => 0, 'markinganonymous' => 0, 'activityformat' => 0, 'timelimit' => 0,
        'submissionattachments' => 0,
        'assignsubmission_onlinetext_enabled' => 1, 'assignsubmission_file_enabled' => 0,
        'assignfeedback_comments_enabled' => 1,
    ]);
});

paso('Unidad 3 — Scratch (enlace)', function () use ($curso) {
    if (existe($curso, 'url', 'Scratch: crea tu proyecto')) return 'ya existía';
    actividad($curso, 'url', 3, [
        'name' => 'Scratch: crea tu proyecto', 'externalurl' => 'https://scratch.mit.edu/projects/editor/',
        'display' => RESOURCELIB_DISPLAY_AUTO, 'printintro' => 0,
        'intro' => '<p>Abre el editor de Scratch y sigue la guía de esta unidad.</p>',
    ]);
});

paso('Unidad 3 — Primeros pasos con Scratch (página)', fn() => pagina($curso, 3, 'Primeros pasos con Scratch',
    '<p>En Scratch programas con <strong>bloques</strong> que se encajan como piezas de lego.</p>'
  . '<ol><li>Abre el editor de Scratch (enlace de esta unidad).</li><li>Elige un personaje (el gato está por defecto).</li><li>Arrastra el bloque <em>«al hacer clic en la bandera verde»</em>.</li><li>Debajo, pon <em>«mover 10 pasos»</em> y <em>«decir ¡Hola! durante 2 segundos»</em>.</li><li>Pulsa la bandera verde y mira lo que hace tu personaje.</li></ol>'
  . '<p><strong>Reto:</strong> haz que tu personaje avance, diga tu nombre y se devuelva al lugar de inicio.</p>'));

$quiz = null;
paso('Cuestionario final', function () use ($curso, $DB, &$quiz) {
    if ($quiz = $DB->get_record('quiz', ['course' => $curso->id, 'name' => 'Cuestionario final'])) return 'ya existía';
    $m = actividad($curso, 'quiz', 3, [
        'name' => 'Cuestionario final',
        'intro' => '<p>Responde las preguntas. Puedes intentarlo las veces que quieras.</p>',
        'timeopen' => 0, 'timeclose' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0,
        'attemptonlast' => 0, 'grademethod' => QUIZ_GRADEHIGHEST, 'decimalpoints' => 2, 'questiondecimalpoints' => -1,
        'attemptduring' => 1, 'correctnessduring' => 1, 'maxmarksduring' => 1, 'marksduring' => 1,
        'specificfeedbackduring' => 1, 'generalfeedbackduring' => 1, 'rightanswerduring' => 1, 'overallfeedbackduring' => 0,
        'attemptimmediately' => 1, 'correctnessimmediately' => 1, 'maxmarksimmediately' => 1, 'marksimmediately' => 1,
        'specificfeedbackimmediately' => 1, 'generalfeedbackimmediately' => 1, 'rightanswerimmediately' => 1, 'overallfeedbackimmediately' => 1,
        'attemptopen' => 1, 'correctnessopen' => 1, 'maxmarksopen' => 1, 'marksopen' => 1,
        'specificfeedbackopen' => 1, 'generalfeedbackopen' => 1, 'rightansweropen' => 1, 'overallfeedbackopen' => 1,
        'attemptclosed' => 1, 'correctnessclosed' => 1, 'maxmarksclosed' => 1, 'marksclosed' => 1,
        'specificfeedbackclosed' => 1, 'generalfeedbackclosed' => 1, 'rightanswerclosed' => 1, 'overallfeedbackclosed' => 1,
        'questionsperpage' => 1, 'shuffleanswers' => 1, 'sumgrades' => 0, 'grade' => 10,
        'timelimit' => 0, 'overduehandling' => 'autosubmit', 'graceperiod' => 86400, 'quizpassword' => '', 'subnet' => '',
        'browsersecurity' => '', 'delay1' => 0, 'delay2' => 0, 'showuserpicture' => 0, 'showblocks' => 0,
        'navmethod' => QUIZ_NAVMETHOD_FREE,
    ]);
    $quiz = $DB->get_record('quiz', ['id' => $m->instance], '*', MUST_EXIST);
    $quiz->cmid = $m->coursemodule;
    return 'creado';
});

// Banco de preguntas: se crean 4 preguntas de opción múltiple (una respuesta correcta)
$preguntas = [
    ['¿Qué es un algoritmo?', ['Una lista de pasos en orden para lograr algo', 'Un tipo de computador', 'Un juego de internet', 'Un dibujo'], 0],
    ['¿Cuál es el patrón? 3, 6, 9, 12, …', ['Se suma 3 cada vez', 'Se resta 3 cada vez', 'Los números son al azar', 'Siempre se suma 1'], 0],
    ['En una receta, ¿qué pasa si cambias el orden de los pasos?', ['Puede que no salga bien', 'Nunca cambia nada', 'La receta se borra', 'Sale mejor siempre'], 0],
    ['En Scratch, ¿qué bloque inicia el programa cuando haces clic en la bandera verde?', ['«al hacer clic en la bandera verde»', '«mover 10 pasos»', '«decir Hola»', '«esperar 1 segundo»'], 0],
];
paso('Preguntas del cuestionario', function () use ($quiz, $preguntas, $DB) {
    if (!$quiz) throw new Exception('no hay cuestionario');
    if ($DB->record_exists('quiz_slots', ['quizid' => $quiz->id])) return 'ya tenía preguntas';
    $ctx = context_module::instance($quiz->cmid);
    $raiz = question_get_top_category($ctx->id, true);
    $manager = new \core_question\category_manager();
    $cat = (object) [
        'name' => 'Preguntas del curso', 'info' => '', 'infoformat' => FORMAT_HTML,
        'stamp' => make_unique_id_code(), 'idnumber' => null, 'contextid' => $ctx->id, 'parent' => $raiz->id,
        'sortorder' => $manager->get_max_sortorder($raiz->id) + 1,
    ];
    $cat->id = $DB->insert_record('question_categories', $cat);

    foreach ($preguntas as $i => [$texto, $opciones, $correcta]) {
        $f = new stdClass();
        $f->category = $cat->id;
        $f->name = 'Pregunta ' . ($i + 1);
        $f->questiontext = ['text' => '<p>' . $texto . '</p>', 'format' => FORMAT_HTML];
        $f->generalfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $f->defaultmark = 1; $f->penalty = 0.3333333; $f->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $f->noanswers = count($opciones); $f->numhints = 0;
        $f->shuffleanswers = 1; $f->answernumbering = 'abc'; $f->showstandardinstruction = 0; $f->single = '1';
        $f->correctfeedback = ['text' => '¡Muy bien!', 'format' => FORMAT_HTML];
        $f->partiallycorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $f->incorrectfeedback = ['text' => 'Repasa la explicación de la unidad e inténtalo otra vez.', 'format' => FORMAT_HTML];
        $f->shownumcorrect = 0;
        $f->answer = []; $f->fraction = []; $f->feedback = [];
        foreach ($opciones as $k => $op) {
            $f->answer[$k] = ['text' => $op, 'format' => FORMAT_PLAIN];
            $f->fraction[$k] = ($k === $correcta) ? '1.0' : '0.0';
            $f->feedback[$k] = ['text' => '', 'format' => FORMAT_HTML];
        }
        $q = new stdClass();
        $q->qtype = 'multichoice'; $q->createdby = get_admin()->id; $q->idnumber = null;
        $q = question_bank::get_qtype('multichoice')->save_question($q, $f);
        quiz_add_quiz_question($q->id, $quiz);
    }
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    return count($preguntas) . ' preguntas';
});

echo ">> Matrícula\n";
paso('Docente y estudiantes matriculados', function () use ($curso, $ids, $DB) {
    $rol = fn(string $r) => $DB->get_field('role', 'id', ['shortname' => $r], MUST_EXIST);
    $n = 0;
    foreach (['docente.martha' => 'editingteacher', 'est.ana' => 'student', 'est.juan' => 'student', 'est.sofia' => 'student'] as $login => $r) {
        if (empty($ids[$login])) continue;
        if (!is_enrolled(context_course::instance($curso->id), $ids[$login])) {
            enrol_try_internal_enrol($curso->id, $ids[$login], $rol($r));
            $n++;
        }
    }
    return "$n matrículas nuevas";
});

purge_all_caches();
echo ($fallos ? "\nTerminó con $fallos fallo(s): revisar arriba y ver moodle/README.md (plan B).\n" : "\nEVA listo.\n");
exit($fallos ? 1 : 0);
