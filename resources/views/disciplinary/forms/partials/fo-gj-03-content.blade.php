{{-- Compat: cuerpo completo en una sola inclusión (pantallas legacy). PDF usa fo-gj-03-body + paginador. --}}
@include('disciplinary.forms.partials.fo-gj-03-opening')
@include('disciplinary.forms.partials.fo-gj-03-charges', [
    'chargesShowLead' => true,
    'chargesIsContinuation' => false,
    'chargesChunk' => $chargesDescription ?? '',
    'chargesShowTail' => true,
])
@include('disciplinary.forms.partials.fo-gj-03-articles')
@include('disciplinary.forms.partials.fo-gj-03-evidence')
