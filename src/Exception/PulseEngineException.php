<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Exception;

/**
 * Root exception for all Pulse-Engine domain errors.
 * Catch this to handle any engine-specific failure.
 */
class PulseEngineException extends \RuntimeException {}
