<?php

namespace App\Bot;

use InvalidArgumentException;

/**
 * La fecha escrita por el usuario no se pudo entender o no es aceptable.
 * El mensaje está pensado para enviarse tal cual por WhatsApp.
 */
final class FechaInvalida extends InvalidArgumentException {}
