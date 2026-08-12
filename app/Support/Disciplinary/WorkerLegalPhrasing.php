<?php

namespace App\Support\Disciplinary;

use App\Enums\EmployeeGender;
use App\Models\Employee;

/**
 * Redacción gramatical por género para formatos disciplinarios (FO-GJ-03, 04, 46, 54).
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

    public function foGj47OpeningSalutation(): string
    {
        return $this->foGj54OpeningSalutation();
    }

    public function foGj45OpeningSalutation(): string
    {
        return $this->foGj54OpeningSalutation();
    }

    /** Bloque de firmas FO-GJ-45: «El trabajador;» / «La trabajadora;» */
    public function foGj45WorkerSignatureLead(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'La trabajadora;',
            EmployeeGender::Masculino => 'El trabajador;',
            default => 'La persona vinculada;',
        };
    }

    /** «NOTIFICAR al trabajador / a la trabajadora…» */
    public function foGj47NotifyWorkerPhrase(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'NOTIFICAR a la trabajadora',
            EmployeeGender::Masculino => 'NOTIFICAR al trabajador',
            default => 'NOTIFICAR a la persona vinculada',
        };
    }

    public function foGj47SuspensionEffectParagraph(): string
    {
        return 'Adicionalmente, me permito advertir que la suspensión tiene como efecto para '.$this->foGj47WorkerArticleNoun().' la interrupción de la obligación de prestar el servicio y para la empresa la interrupción del pago de los salarios y demás prestaciones asociadas.';
    }

    /** «el trabajador» / «la trabajadora» / «la persona vinculada» */
    public function foGj47WorkerArticleNoun(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'la trabajadora',
            EmployeeGender::Masculino => 'el trabajador',
            default => 'la persona vinculada',
        };
    }

    public function foGj47FactsAnalysisParagraph(): string
    {
        return 'Una vez analizados los hechos y pruebas presentados, así como sus explicaciones, se ha concluido que usted incurrió en la mencionada falta, sin que los argumentos que expuso en la diligencia de versión libre justifiquen su actuar, toda vez que, no cumplió a cabalidad con las instrucciones propias para desempeñar su cargo y, por lo tanto, se encuentra evidencia fehaciente de su inobservancia, procediendo así, a imponerle la sanción que se le notifica por este medio. Sin embargo, por parte de la empresa se decide brindarle una oportunidad para demostrar su compromiso dejando claro que no es concebible una segunda comisión de ningún tipo de falta.';
    }

    public function foGj47PostArticlesClosingParagraph(): string
    {
        return 'Bajo lo anteriormente referido, procede la imposición de la sanción disciplinaria y tras observarse que es la primera vez que incurre en la conducta. Se le requiere para que, en lo sucesivo, dé cumplimiento a las consignas generales y particulares de SJ SEGURIDAD LTDA, particularmente teniendo en cuenta los hechos que dieron lugar al informe disciplinario.';
    }

    public function foGj47AppealParagraph(): string
    {
        return 'Contra la decisión de sanción procede el recurso de apelación, el cual, si lo considera pertinente, podrá presentar en un plazo máximo de dos (02) días hábiles a partir de la fecha de notificación del presente documento, si no lo hace la sanción quedará en firme, ejecutándose así.';
    }

    public function foGj54ScheduledHearingPhrase(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'usted se encontraba citada para ser escuchada en diligencia disciplinaria.',
            EmployeeGender::Masculino => 'usted se encontraba citado para ser escuchado en diligencia disciplinaria.',
            default => 'usted tenía programada la diligencia disciplinaria.',
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

    public function foGj46HearingLeadPhrase(FoGj46HearingLead $lead): string
    {
        return match ($lead) {
            FoGj46HearingLead::Surtida => 'y una vez surtida la',
            FoGj46HearingLead::Citado => match ($this->variant) {
                EmployeeGender::Femenino => 'usted fue citada a una',
                EmployeeGender::Masculino => 'usted fue citado a una',
                default => 'usted fue citado(a) a una',
            },
        };
    }

    /**
     * Puente fijo tras la fecha de diligencia/citación, antes de la fecha de incumplimiento.
     * «Citado» inserta el párrafo de inasistencia; «surtida» solo el análisis.
     */
    public function foGj46PostHearingBridge(?FoGj46HearingLead $lead): string
    {
        $analysis = 'se procedió con el análisis integral de los hechos investigados y del material probatorio recaudado. Como resultado, se estableció que el día';

        if ($lead === FoGj46HearingLead::Citado) {
            return 'con el fin de escuchar sus descargos frente a los hechos investigados. No obstante, usted no asistió a la citación ni presentó una excusa o justificación por su inasistencia, razón por la cual el proceso continuó, en garantía del debido proceso. '.$analysis;
        }

        return $analysis;
    }

    /** Sustantivo individual: «en su calidad de …». */
    public function foGj46WorkerNoun(): string
    {
        return match ($this->variant) {
            EmployeeGender::Femenino => 'trabajadora',
            EmployeeGender::Masculino => 'trabajador',
            default => 'trabajador(a)',
        };
    }

    public function foGj46ExhortationParagraph1(): string
    {
        $noun = $this->foGj46WorkerNoun();

        return 'No obstante, lo anterior, se le requiere para que, en lo sucesivo, cumpla de manera estricta con las responsabilidades y funciones inherentes a su cargo, teniendo en cuenta los hechos que dieron lugar al informe disciplinario. El incumplimiento evidenciado constituye una vulneración a las obligaciones y prohibiciones que le son exigibles en su calidad de '.$noun.', reflejando una falta de cuidado y diligencia en el desarrollo de las labores que le han sido encomendadas. Dicha situación resulta incompatible con el nivel de responsabilidad, compromiso y proactividad que deben caracterizar el desempeño de los trabajadores de la empresa.';
    }

    public function foGj46ExhortationParagraph2(): string
    {
        return 'Por lo anterior, le invitamos a revisar minuciosamente su desempeño y a ajustar su comportamiento a las obligaciones y responsabilidades que le han sido confiadas, buscando siempre la mejora continua en sus funciones. Además, se le recuerda que, en caso de reincidencia en esta o en cualquier conducta que comprometa la calidad, competencia, eficacia, eficiencia y responsabilidad en el ejercicio de sus funciones, se procederá a aplicar correctivos que corresponden.';
    }
}
