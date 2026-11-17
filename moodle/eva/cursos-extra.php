<?php
// ============================================================================
//  Cursos de ejemplo adicionales para el EVA (4 cursos de primaria).
//  Es idempotente: si un curso ya existe, lo omite. Se ejecuta con
//  scripts/crear-cursos-extra.sh (no hace falta reconstruir la imagen).
//  Matricula a la docente y a los tres estudiantes que crea crear-eva.php.
// ============================================================================
define('CLI_SCRIPT', true);
require('/var/www/moodle/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/lib/resourcelib.php');

\core\session\manager::set_user(get_admin());

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
function tarea(object $curso, int $sec, string $nombre, string $texto): void {
    if (existe($curso, 'assign', $nombre)) return;
    actividad($curso, 'assign', $sec, [
        'name' => $nombre, 'intro' => $texto,
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
}
function foro(object $curso, string $nombre, string $texto): void {
    if (existe($curso, 'forum', $nombre)) return;
    actividad($curso, 'forum', 0, [
        'name' => $nombre, 'type' => 'general', 'assessed' => 0, 'scale' => 0,
        'forcesubscribe' => 0, 'grade_forum' => 0, 'intro' => $texto,
    ]);
}

$cursos = [
    [
        'nombre' => 'Lectura y escritura creativa', 'corto' => 'LECT-4-5',
        'resumen' => '<p><strong>Para estudiantes de 4.º y 5.º.</strong> Leerás cuentos cortos, aprenderás a contar lo que entendiste y escribirás tu propia historia.</p><ul><li>Leer con atención y encontrar la idea principal.</li><li>Reconocer personajes, lugar y problema de un cuento.</li><li>Escribir un cuento con inicio, nudo y final.</li></ul>',
        'unidades' => ['Unidad 1: Leo y comprendo', 'Unidad 2: Escribo mi propio cuento'],
        'bienvenida' => '<h3>¡Bienvenido a Lectura y escritura creativa!</h3><p>Vas a leer cuentos y después escribirás el tuyo. Si algo no lo entiendes, pregunta en el foro.</p>',
        'paginas' => [
            [1, 'El cuento y sus partes', '<p>Todo cuento tiene <strong>personajes</strong>, un <strong>lugar</strong> y un <strong>problema</strong> que se resuelve. Lee con atención y busca estas tres cosas.</p>'],
            [2, 'Cómo escribir tu cuento', '<p>Tu cuento necesita tres momentos: <strong>inicio</strong> (quién y dónde), <strong>nudo</strong> (qué problema aparece) y <strong>final</strong> (cómo se resuelve).</p>'],
        ],
        'tarea' => [2, 'Tarea: escribe tu cuento corto', '<p>Escribe un cuento de 8 a 10 renglones con inicio, nudo y final. Ponle un título que llame la atención.</p>'],
    ],
    [
        'nombre' => 'Matemáticas con juegos', 'corto' => 'MAT-4-5',
        'resumen' => '<p><strong>Para estudiantes de 4.º y 5.º.</strong> Practicarás las operaciones y las fracciones jugando.</p><ul><li>Resolver problemas de suma, resta, multiplicación y división.</li><li>Entender qué es una fracción.</li><li>Inventar tus propios problemas.</li></ul>',
        'unidades' => ['Unidad 1: Operaciones sin miedo', 'Unidad 2: Las fracciones en la vida diaria'],
        'bienvenida' => '<h3>¡Bienvenido a Matemáticas con juegos!</h3><p>Aquí practicarás con retos cortos. Equivocarse es parte de aprender.</p>',
        'paginas' => [
            [1, 'Problemas de la tienda escolar', '<p>Si una galleta cuesta $800 y compras 3, ¿cuánto pagas? Piensa qué operación necesitas antes de calcular.</p>'],
            [2, '¿Qué es una fracción?', '<p>Una fracción es una parte de un entero. Si partes una pizza en 4 y comes 1 pedazo, comiste <strong>1/4</strong>.</p>'],
        ],
        'tarea' => [2, 'Tarea: inventa un problema', '<p>Inventa un problema con fracciones sobre algo de tu casa y escríbelo con su respuesta.</p>'],
    ],
    [
        'nombre' => 'Ciencias naturales: el cuerpo humano', 'corto' => 'CIEN-4-5',
        'resumen' => '<p><strong>Para estudiantes de 4.º y 5.º.</strong> Descubrirás cómo funciona tu cuerpo y cómo cuidarlo.</p><ul><li>Nombrar los sistemas principales del cuerpo.</li><li>Explicar cómo respiramos y cómo late el corazón.</li><li>Practicar hábitos saludables.</li></ul>',
        'unidades' => ['Unidad 1: Mi cuerpo por dentro', 'Unidad 2: Cuido mi salud'],
        'bienvenida' => '<h3>¡Bienvenido a Ciencias naturales!</h3><p>Observa, pregunta y comprueba. Una buena científica o un buen científico siempre pregunta.</p>',
        'paginas' => [
            [1, 'El corazón y los pulmones', '<p>El <strong>corazón</strong> manda la sangre por todo el cuerpo y los <strong>pulmones</strong> traen el aire que necesitamos. Pon la mano en tu pecho y siente cómo late.</p>'],
            [2, 'Hábitos saludables', '<p>Dormir bien, comer frutas y verduras, tomar agua y hacer ejercicio ayudan a que tu cuerpo funcione mejor.</p>'],
        ],
        'tarea' => [2, 'Tarea: mi plan saludable', '<p>Escribe 5 cosas que vas a hacer esta semana para cuidar tu salud.</p>'],
    ],
    [
        'nombre' => 'Inglés: mis primeras frases', 'corto' => 'ING-4-5',
        'resumen' => '<p><strong>Para estudiantes de 4.º y 5.º.</strong> Aprenderás a saludar, presentarte y hablar de tu familia en inglés.</p><ul><li>Saludar y despedirte.</li><li>Presentarte con tu nombre y tu edad.</li><li>Nombrar a los miembros de tu familia.</li></ul>',
        'unidades' => ['Unidad 1: Hello! Saludos y presentaciones', 'Unidad 2: My family — mi familia'],
        'bienvenida' => '<h3>Welcome! ¡Bienvenido a Inglés!</h3><p>Aquí aprenderás frases cortas para usar todos los días. Practica en voz alta.</p>',
        'paginas' => [
            [1, 'Saludos y presentaciones', '<p><strong>Hello</strong> = hola. <strong>My name is…</strong> = me llamo… <strong>I am ten years old</strong> = tengo diez años. Practica presentándote con un compañero.</p>'],
            [2, 'My family', '<p><strong>mother</strong> = mamá, <strong>father</strong> = papá, <strong>sister</strong> = hermana, <strong>brother</strong> = hermano. Di quién es quién en tu familia.</p>'],
        ],
        'tarea' => [2, 'Tarea: presenta a tu familia', '<p>Escribe 4 frases en inglés presentando a tu familia. Ejemplo: <em>This is my mother. Her name is Luz.</em></p>'],
    ],
];

$catid = $DB->get_field_select('course_categories', 'MIN(id)', 'parent = 0');
$rol = fn(string $r) => $DB->get_field('role', 'id', ['shortname' => $r], MUST_EXIST);
$matriculas = ['docente.martha' => 'editingteacher', 'est.ana' => 'student', 'est.juan' => 'student', 'est.sofia' => 'student'];
$fallos = 0;

foreach ($cursos as $c) {
    echo ">> {$c['nombre']}\n";
    try {
        $curso = $DB->get_record('course', ['shortname' => $c['corto']]);
        if ($curso) {
            echo "  [OK]    ya existía\n";
        } else {
            $curso = create_course((object) [
                'fullname' => $c['nombre'], 'shortname' => $c['corto'], 'category' => $catid,
                'idnumber' => '', 'newsitems' => 1, 'numsections' => count($c['unidades']),
                'startdate' => usergetmidnight(time()), 'format' => 'topics', 'visible' => 1, 'lang' => '',
                'summaryformat' => FORMAT_HTML, 'summary' => $c['resumen'],
            ]);
            course_create_sections_if_missing($curso, range(1, count($c['unidades'])));
            $DB->set_field('course_sections', 'name', 'Bienvenida', ['course' => $curso->id, 'section' => 0]);
            foreach ($c['unidades'] as $i => $nombre) {
                $DB->set_field('course_sections', 'name', $nombre, ['course' => $curso->id, 'section' => $i + 1]);
            }
            rebuild_course_cache($curso->id, true);
            pagina($curso, 0, 'Presentación del curso', $c['bienvenida']);
            foro($curso, 'Foro de preguntas', '<p>Pregunta lo que no entiendas. Escribe con respeto.</p>');
            foreach ($c['paginas'] as [$sec, $nombre, $html]) { pagina($curso, $sec, $nombre, $html); }
            tarea($curso, $c['tarea'][0], $c['tarea'][1], $c['tarea'][2]);
            echo "  [OK]    curso creado con sus actividades\n";
        }
        $ctx = context_course::instance($curso->id);
        foreach ($matriculas as $login => $r) {
            $u = $DB->get_record('user', ['username' => $login, 'mnethostid' => $CFG->mnet_localhost_id]);
            if ($u && !is_enrolled($ctx, $u->id)) { enrol_try_internal_enrol($curso->id, $u->id, $rol($r)); }
        }
        echo "  [OK]    docente y estudiantes matriculados\n";
    } catch (Throwable $e) {
        $fallos++;
        echo "  [FALLO] " . $e->getMessage() . "\n";
    }
}
purge_all_caches();
echo $fallos ? "\nTerminó con $fallos fallo(s).\n" : "\nCursos listos.\n";
exit($fallos ? 1 : 0);
