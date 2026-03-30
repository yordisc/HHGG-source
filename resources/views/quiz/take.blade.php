@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl">
        <livewire:quiz-runner :cert-type="$certType" />
    </section>
@endsection
