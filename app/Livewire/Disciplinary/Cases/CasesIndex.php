<?php

namespace App\Livewire\Disciplinary\Cases;

use App\Enums\Disciplinary\CaseBucket;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Disciplinarios')]
class CasesIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $bucket = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'lawyer')]
    public string $lawyerId = '';

    #[Url]
    public string $city = '';

    #[Url(as: 'fault')]
    public string $faultId = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public int $perPage = 15;

    /** Abre el modal FO-GJ-51 en esta misma pantalla. */
    public bool $showFo51Modal = false;

    /** Si true, el submodal «Cargar PDF» queda abierto al mostrar el formulario. */
    public bool $fo51OpenPdfFirst = false;

    public ?string $fo51PrefillName = null;

    public ?string $fo51PrefillDocument = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', DisciplinaryCase::class);

        if (request()->boolean('informe_modal')) {
            Gate::authorize('generateFo51Inform', DisciplinaryCase::class);

            $this->showFo51Modal = true;
            $this->fo51OpenPdfFirst = request()->boolean('cargar_pdf');
            $n = request()->string('nombre')->trim()->toString();
            $c = request()->string('cedula')->trim()->toString();
            $this->fo51PrefillName = $n !== '' ? $n : null;
            $this->fo51PrefillDocument = $c !== '' ? $c : null;
        }
    }

    public function openFo51Modal(bool $openPdfFirst = false): void
    {
        Gate::authorize('generateFo51Inform', DisciplinaryCase::class);
        $this->fo51PrefillName = null;
        $this->fo51PrefillDocument = null;
        $this->fo51OpenPdfFirst = $openPdfFirst;
        $this->showFo51Modal = true;
    }

    public function closeFo51Modal(): void
    {
        $this->showFo51Modal = false;
        $this->fo51OpenPdfFirst = false;
    }

    public function updating($prop): void
    {
        if (in_array($prop, ['search', 'bucket', 'status', 'lawyerId', 'city', 'faultId', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function setBucket(string $bucket): void
    {
        $this->bucket = $this->bucket === $bucket ? '' : $bucket;
        $this->status = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'bucket', 'status', 'lawyerId', 'city', 'faultId', 'from', 'to']);
        $this->resetPage();
    }

    #[Computed]
    public function quickStats(): array
    {
        $base = DisciplinaryCase::query()
            ->forDisciplinaryActor(auth()->user())
            ->select('current_status', DB::raw('COUNT(*) as total'))
            ->groupBy('current_status')
            ->pluck('total', 'current_status');

        $totals = ['pendientes' => 0, 'en_proceso' => 0, 'finalizados' => 0];
        foreach (CaseStatus::cases() as $s) {
            $totals[match ($s->bucket()) {
                CaseBucket::PENDIENTE => 'pendientes',
                CaseBucket::EN_PROCESO => 'en_proceso',
                CaseBucket::FINALIZADO => 'finalizados',
            }] += (int) ($base[$s->value] ?? 0);
        }

        return $totals;
    }

    #[Computed]
    public function lawyers()
    {
        return User::query()->role('abogado')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function faults()
    {
        return Fault::active()->ordered()->get(['id', 'name']);
    }

    #[Computed]
    public function cities(): array
    {
        return DisciplinaryCase::query()
            ->forDisciplinaryActor(auth()->user())
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();
    }

    public function render()
    {
        $cases = DisciplinaryCase::query()
            ->forDisciplinaryActor(auth()->user())
            ->with(['personnel:id,first_name,last_name,document_number', 'assignedLawyer:id,name'])
            ->withCount('faults')
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->bucket !== '', fn ($q) => $q->bucket(CaseBucket::from($this->bucket)))
            ->when($this->status !== '', fn ($q) => $q->withStatus(CaseStatus::from($this->status)))
            ->when($this->lawyerId !== '', fn ($q) => $q->assignedTo((int) $this->lawyerId))
            ->when($this->city !== '', fn ($q) => $q->inCity($this->city))
            ->when($this->faultId !== '', fn ($q) => $q->withFault((int) $this->faultId))
            ->when($this->from !== '' && $this->to !== '', fn ($q) => $q->openedBetween($this->from, $this->to))
            ->orderByDesc('opened_at')
            ->paginate($this->perPage);

        return view('livewire.disciplinary.cases.index', [
            'cases' => $cases,
            'statuses' => CaseStatus::cases(),
        ]);
    }
}
