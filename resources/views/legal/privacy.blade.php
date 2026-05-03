@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Legal</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Política de privacidad</h1>
            <p class="mt-3 text-slate-600">Aquí se resume cómo se manejan los datos dentro de la aplicación.</p>
        </div>

        <div class="space-y-4 text-sm leading-7 text-slate-700">
            <p>Solo se almacenan los datos necesarios para operar la plataforma, gestionar usuarios y emitir certificados.
            </p>
            <p>El acceso administrativo está restringido y las acciones sensibles pueden registrarse para fines de auditoría
                y seguridad.</p>
            <p>Si el despliegue incorpora integraciones externas, el tratamiento de datos seguirá las políticas del
                proveedor correspondiente y la configuración activa del sistema.</p>
        </div>
    </section>
@endsection
