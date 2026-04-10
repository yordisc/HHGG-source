<aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-3">
        <h3 class="text-sm font-bold text-slate-900">Certificados recientes</h3>
        <p class="text-xs text-slate-500">Revoca o restaura certificados emitidos de esta certificación.</p>
    </div>

    @if ($recentCertificates->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-600">
            No hay certificados emitidos aún.
        </div>
    @else
        <div class="space-y-3">
            @foreach ($recentCertificates as $issuedCertificate)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold text-slate-900 break-all">{{ $issuedCertificate->serial }}</p>
                    <p class="mt-1 text-xs text-slate-600">{{ $issuedCertificate->first_name }} {{ $issuedCertificate->last_name }}</p>
                    <p class="text-xs text-slate-500">Emitido: {{ $issuedCertificate->issued_at?->format('Y-m-d H:i') ?? 'N/D' }}</p>

                    @if ($issuedCertificate->revoked_at)
                        <div class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-2 py-2 text-xs text-rose-800">
                            Revocado: {{ $issuedCertificate->revoked_at->format('Y-m-d H:i') }}
                            @if ($issuedCertificate->revoked_reason)
                                <p class="mt-1">Motivo: {{ $issuedCertificate->revoked_reason }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.certifications.certificates.restore', [$certification, $issuedCertificate]) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                Restaurar certificado
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.certifications.certificates.revoke', [$certification, $issuedCertificate]) }}" class="mt-2 space-y-2">
                            @csrf
                            <label class="block text-xs font-semibold text-slate-700">
                                Motivo de revocación
                                <textarea name="reason" rows="2" required maxlength="500" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1 text-xs" placeholder="Ej: detección de inconsistencias en datos de emisión"></textarea>
                            </label>
                            <button type="submit" class="w-full rounded-lg border border-rose-300 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50" onclick="return confirm('¿Confirmas revocar este certificado?');">
                                Revocar certificado
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</aside>
