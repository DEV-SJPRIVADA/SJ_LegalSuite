<?php

namespace App\Support\Disciplinary;

use App\Enums\EmployeeGender;
use App\Models\Employee;

/**
 * Redacción gramatical por género para formatos disciplinarios (FO-GJ-03, 04, 54).
 */
final class WorkerLegalPhrasing
{
    private const TRASLADO_COMMON = ' Se le hace saber que, el llamamiento a la diligencia de descargos no es propia de sanción disciplinaria, por el contrario, con ella buscamos garantizar el debido proceso, el derecho a la contradicción y a la defensa, conforme lo cual, podrá usted asistir con dos (02) testigos, controvertir las pruebas en su contra y allegar las pruebas que considere pertinentes informando por escrito al correo relacioneslaborales@sjsp.com.co con mínimo dos (02) horas de anticipación a la diligencia. En caso de tener alguna situación que imposibilite su presencia, deberá remitir dentro de los dos (2) días hábiles siguientes, la debida excusa para fijar nueva fecha, de lo contrario se entiende su renuncia al derecho a la defensa y se tendrán por cierto los hechos que motivaron la apertura del presente proceso disciplinario.';

    private function __construct(
        private readonly EmployeeGender $variant,
    ) {}

    public static function fromGender(?EmployeeGender $gender): self
    {
        return match ($gender) {
            EmployeeGender::Femenino => new self(EmployeeGender::Femenino),
            EmployeeGender::Masculino => new self(EmployeeGender::Masculino),
            default => new self(EmployeeGender::NoIndica),
        };
    }

    public static function fromEmployee(?Employee $employee): self
    {
        return self::fromGender($employee?->gender);
    }

    public static function masculine(): self
    {
        return new self(EmployeeGender::Masculino);
    }

    public function hasDefiniteGender(): bool
    {
        return in_array($this->variant, [EmployeeGender::Masculino, EmployeeGender::Femenino], true);
    }

    public function foGj03OpeningSalutation(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'Respetada trabajadora;',
            EmployeeGender::Masculino => 'Respetado trabajador;',
            default => 'Cordial saludo;',
        };
    }

    public function foGj03CitationVerb(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'citarla',
            EmployeeGender::Masculino => 'citarlo',
            default => 'citarle',
        };
    }

    public function foGj03DefenseHearingPhrase(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'ser escuchada',
            EmployeeGender::Masculino => 'ser escuchado',
            default => 'comparecer',
        };
    }

    public function foGj03EvidenceTrasladoText(): string
    {
        $lead = match ($this->variant) {
            EmployeeGender::Femenino => 'Se corre traslado a la trabajadora de todas y cada una de las pruebas que fundamentan los cargos formulados.',
            EmployeeGender::Masculino => 'Se corre traslado al trabajador de todas y cada una de las pruebas que fundamentan los cargos formulados.',
            default => 'Se le corre traslado de todas y cada una de las pruebas que fundamentan los cargos formulados.',
        };

        return $lead.self::TRASLADO_COMMON;
    }

    public function foGj04PartyLabel(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'LA TRABAJADORA:',
            EmployeeGender::Masculino => 'EL TRABAJADOR:',
            default => 'PERSONA VINCULADA:',
        };
    }

    public function foGj04ManifestationIntro(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'Una vez enterada y entiendo perfectamente sus derechos, LA TRABAJADORA, manifestó:',
            EmployeeGender::Masculino => 'Una vez enterado y entiendo perfectamente sus derechos, EL TRABAJADOR, manifestó:',
            default => 'Una vez informado de sus derechos y entendiéndolos perfectamente, manifestó:',
        };
    }

    public function foGj04FreeVersionPhrase(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'de la trabajadora',
            EmployeeGender::Masculino => 'del trabajador',
            default => 'de la persona vinculada',
        };
    }

    public function foGj04SignatureLabel(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'TRABAJADORA,',
            EmployeeGender::Masculino => 'TRABAJADOR,',
            default => 'PERSONA VINCULADA,',
        };
    }

    public function foGj54OpeningSalutation(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'Respetada colaboradora.',
            EmployeeGender::Masculino => 'Respetado colaborador.',
            default => 'Cordial saludo.',
        };
    }

    public function foGj54ScheduledHearingPhrase(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'horas se encontraba citada usted para ser escuchada en diligencia disciplinaria, sobre los hechos ocurridos el pasado',
            EmployeeGender::Masculino => 'horas se encontraba citado usted para ser escuchado en diligencia disciplinaria, sobre los hechos ocurridos el pasado',
            default => 'horas tenía usted programada la diligencia disciplinaria para ser escuchado sobre los hechos ocurridos el pasado',
        };
    }

    public function foGj54SignatureSectionLabel(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'La Trabajadora;',
            EmployeeGender::Masculino => 'El Trabajador;',
            default => 'Persona vinculada;',
        };
    }
}
