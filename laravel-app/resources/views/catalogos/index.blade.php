@extends('layouts.app')
@section('title', 'Catálogos')
@section('breadcrumb', 'Catálogos')

@push('styles')
    <style>
        :root{--gold:#f5c842;--gold-b:#ead96a;--gold-m:#d4a017;--gold-l:#fffbeb;--gold-d:#9a6e10;}
        @keyframes fadeDown{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes slideUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

        /* Tabs */
        .tabs-bar{display:flex;gap:0;border-bottom:2px solid var(--gold-b);margin-bottom:24px;animation:fadeDown .4s ease-out;}
        .tab-btn{padding:12px 24px;font-size:13px;font-weight:700;color:var(--text-muted);background:none;border:none;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .15s;display:flex;align-items:center;gap:8px;white-space:nowrap;}
        .tab-btn:hover{color:var(--text-primary);background:var(--gold-l);}
        .tab-btn.active{color:var(--gold-d);border-bottom-color:var(--gold-m);background:var(--gold-l);}
        .tab-cnt{background:var(--gold-m);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:800;}
        .tab-panel{display:none;animation:slideUp .35s ease-out;}
        .tab-panel.active{display:block;}

        /* Section card */
        .cat-card{background:#fff;border:1.5px solid var(--gold-b);border-radius:14px;overflow:hidden;}
        .cat-card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--gold-b);background:var(--gold-l);}
        .cat-card-title{font-size:14px;font-weight:800;color:var(--text-primary);}
        .cat-card-desc{font-size:12px;color:var(--text-muted);margin-top:2px;}

        /* Table */
        .cat-table{width:100%;border-collapse:collapse;}
        .cat-table thead tr{background:#0f172a;color:#fff;}
        .cat-table thead th{padding:9px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;}
        .cat-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s;}
        .cat-table tbody tr:hover{background:#fffdf5;}
        .cat-table tbody td{padding:11px 14px;font-size:13px;vertical-align:middle;}
        .cat-table tbody tr:last-child{border-bottom:none;}

        /* Action buttons */
        .act-btn{width:30px;height:30px;border-radius:7px;border:1px solid var(--gold-b);background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;color:var(--text-muted);}
        .act-btn:hover{background:var(--gold-l);border-color:var(--gold-m);color:var(--gold-d);}
        .act-btn.del:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626;}

        /* Empty state */
        .cat-empty{text-align:center;padding:40px 24px;color:var(--text-muted);}

        /* Inline add form */
        .add-form{background:#f8fafc;border-top:1px solid var(--gold-b);padding:14px 18px;display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;}
        .add-form-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:4px;}
        .add-form input{height:38px;border:1.5px solid var(--gold-b);border-radius:8px;font-size:13px;background:#fff;outline:none;padding:0 12px;transition:border-color .15s;min-width:180px;}
        .add-form input:focus{border-color:var(--gold-m);box-shadow:0 0 0 3px #f5c84220;}
        .add-form .btn-add-inline{height:38px;padding:0 16px;background:var(--gold);color:#000;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap;}
        .add-form .btn-add-inline:hover{background:var(--gold-m);}

        /* Badge pill */
        .num-badge{display:inline-block;background:var(--gold-l);border:1px solid var(--gold-b);border-radius:5px;padding:2px 8px;font-size:11px;font-weight:600;color:var(--gold-d);font-family:'DM Mono',monospace;}
    </style>
@endpush

@section('content')
    <div class="page-header" style="animation:fadeDown .4s ease-out;">
        <div>
            <h1 class="page-title">Catálogos</h1>
            <p class="page-desc">Administra choferes, maquinaria y tipos de agregados del sistema.</p>
        </div>
    </div>

    {{-- ── TABS ── --}}
    <div class="tabs-bar">
        <button class="tab-btn active" onclick="switchTab('choferes',this)">
            🧑‍✈️ Choferes <span class="tab-cnt" id="cntChofer">{{ $choferes->count() }}</span>
        </button>
        <button class="tab-btn" onclick="switchTab('maquinarias',this)">
            ⚙️ Maquinaria <span class="tab-cnt" id="cntMaq">{{ $maquinarias->count() }}</span>
        </button>
        <button class="tab-btn" onclick="switchTab('agregados',this)">
            🪨 Agregados <span class="tab-cnt" id="cntAgr">{{ $agregados->count() }}</span>
        </button>
    </div>

    {{-- ══════════════ CHOFERES ══════════════ --}}
    <div class="tab-panel active" id="tab-choferes">
        <div class="cat-card">
            <div class="cat-card-header">
                <div>
                    <div class="cat-card-title">Choferes registrados</div>
                    <div class="cat-card-desc">Operadores disponibles para asignar a cotizaciones</div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="cat-table" id="tblChofer">
                    <thead><tr><th>#</th><th>Nombre</th><th>Ap. Paterno</th><th>Ap. Materno</th><th style="text-align:right;">Acc.</th></tr></thead>
                    <tbody id="bodyChofer">
                    @forelse($choferes as $i => $ch)
                        <tr data-id="{{ $ch->id_chofer }}">
                            <td style="color:var(--text-muted);font-size:11px;">{{ $i+1 }}</td>
                            <td style="font-weight:600;">{{ $ch->nombres }}</td>
                            <td>{{ $ch->apellido_paterno ?? '—' }}</td>
                            <td>{{ $ch->apellido_materno ?? '—' }}</td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:4px;justify-content:flex-end;">
                                    <button class="act-btn" onclick="editarChofer({{ $ch->id_chofer }},'{{ addslashes($ch->nombres) }}','{{ addslashes($ch->apellido_paterno ?? '') }}','{{ addslashes($ch->apellido_materno ?? '') }}')" title="Editar">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button class="act-btn del" onclick="eliminarItem('chofer',{{ $ch->id_chofer }},'{{ addslashes($ch->nombres) }}')" title="Eliminar">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyChofer"><td colspan="5" class="cat-empty">Sin choferes registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Add form --}}
            <div class="add-form" id="addFormChofer">
                <div>
                    <div class="add-form-lbl">Nombres *</div>
                    <input type="text" id="cNombres" placeholder="Ej: SEGUNDO PESANTES">
                </div>
                <div>
                    <div class="add-form-lbl">Ap. Paterno</div>
                    <input type="text" id="cApPaterno" placeholder="Apellido paterno">
                </div>
                <div>
                    <div class="add-form-lbl">Ap. Materno</div>
                    <input type="text" id="cApMaterno" placeholder="Apellido materno">
                </div>
                <button class="btn-add-inline" onclick="guardarChofer()">+ Agregar Chofer</button>
            </div>
        </div>
    </div>

    {{-- ══════════════ MAQUINARIAS ══════════════ --}}
    <div class="tab-panel" id="tab-maquinarias">
        <div class="cat-card">
            <div class="cat-card-header">
                <div>
                    <div class="cat-card-title">Maquinaria registrada</div>
                    <div class="cat-card-desc">Volquetes, cisternas, motoniveladoras y otros equipos</div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="cat-table" id="tblMaq">
                    <thead><tr><th>#</th><th>Nombre / Tipo</th><th>N° Máquina</th><th style="text-align:right;">Acc.</th></tr></thead>
                    <tbody id="bodyMaq">
                    @forelse($maquinarias as $i => $m)
                        <tr data-id="{{ $m->id_maquinaria }}">
                            <td style="color:var(--text-muted);font-size:11px;">{{ $i+1 }}</td>
                            <td style="font-weight:600;">{{ $m->nombre }}</td>
                            <td>
                                @if($m->numero_maquina)
                                    <span class="num-badge">{{ $m->numero_maquina }}</span>
                                @else — @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:4px;justify-content:flex-end;">
                                    <button class="act-btn" onclick="editarMaq({{ $m->id_maquinaria }},'{{ addslashes($m->nombre) }}','{{ addslashes($m->numero_maquina ?? '') }}')" title="Editar">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button class="act-btn del" onclick="eliminarItem('maquinaria',{{ $m->id_maquinaria }},'{{ addslashes($m->nombre) }}')" title="Eliminar">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyMaq"><td colspan="4" class="cat-empty">Sin maquinaria registrada.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="add-form">
                <div>
                    <div class="add-form-lbl">Nombre / Tipo *</div>
                    <input type="text" id="mNombre" placeholder="Ej: VOLQUETE, MOTONIVELADORA…" style="min-width:220px;">
                </div>
                <div>
                    <div class="add-form-lbl">N° Máquina</div>
                    <input type="text" id="mNumero" placeholder="Ej: CAU782" style="min-width:140px;">
                </div>
                <button class="btn-add-inline" onclick="guardarMaq()">+ Agregar Maquinaria</button>
            </div>
        </div>
    </div>

    {{-- ══════════════ AGREGADOS ══════════════ --}}
    <div class="tab-panel" id="tab-agregados">
        <div class="cat-card">
            <div class="cat-card-header">
                <div>
                    <div class="cat-card-title">Tipos de Agregados</div>
                    <div class="cat-card-desc">Arena gruesa, afirmado, eliminación de desmonte, etc.</div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="cat-table" id="tblAgr">
                    <thead><tr><th>#</th><th>Nombre del Agregado</th><th>Código</th><th style="text-align:right;">Acc.</th></tr></thead>
                    <tbody id="bodyAgr">
                    @forelse($agregados as $i => $a)
                        <tr data-id="{{ $a->id_agregado }}">
                            <td style="color:var(--text-muted);font-size:11px;">{{ $i+1 }}</td>
                            <td style="font-weight:600;">{{ $a->nombre }}</td>
                            <td>
                                @if($a->numero_agregado)
                                    <span class="num-badge">{{ $a->numero_agregado }}</span>
                                @else — @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:4px;justify-content:flex-end;">
                                    <button class="act-btn" onclick="editarAgr({{ $a->id_agregado }},'{{ addslashes($a->nombre) }}','{{ addslashes($a->numero_agregado ?? '') }}')" title="Editar">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button class="act-btn del" onclick="eliminarItem('agregado',{{ $a->id_agregado }},'{{ addslashes($a->nombre) }}')" title="Eliminar">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyAgr"><td colspan="4" class="cat-empty">Sin agregados registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="add-form">
                <div>
                    <div class="add-form-lbl">Nombre *</div>
                    <input type="text" id="aNombre" placeholder="Ej: AFIRMADO, ARENA GRUESA…" style="min-width:220px;">
                </div>
                <div>
                    <div class="add-form-lbl">Código</div>
                    <input type="text" id="aNumero" placeholder="Código interno" style="min-width:140px;">
                </div>
                <button class="btn-add-inline" onclick="guardarAgr()">+ Agregar Tipo</button>
            </div>
        </div>
    </div>

    {{-- ── MODAL EDITAR ── --}}
    <div class="modal-overlay" id="modalEdit">
        <div class="modal" style="max-width:460px;">
            <div class="modal-header">
                <h2 id="modalEditTitle">Editar</h2>
                <p id="modalEditDesc"></p>
                <button onclick="document.getElementById('modalEdit').classList.remove('open')"
                        style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body" style="padding:24px;" id="modalEditBody"></div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="document.getElementById('modalEdit').classList.remove('open')">Cancelar</button>
                <button class="btn btn-primary" id="btnGuardarEdit" onclick="guardarEdit()">Guardar</button>
            </div>
        </div>
    </div>

    {{-- ── MODAL ELIMINAR ── --}}
    <div class="modal-overlay" id="modalDel">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header" style="background:#7f1d1d;">
                <h2>Eliminar registro</h2>
                <p id="modalDelDesc"></p>
                <button onclick="document.getElementById('modalDel').classList.remove('open')"
                        style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body" style="padding:20px 24px;">
                <p style="font-size:14px;">¿Confirmas que deseas eliminar este registro?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="document.getElementById('modalDel').classList.remove('open')">Cancelar</button>
                <button class="btn" style="background:#dc2626;color:#fff;" id="btnConfDel">Eliminar</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast" style="position:fixed;bottom:24px;right:24px;z-index:9999;padding:13px 20px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);transform:translateY(80px);opacity:0;transition:all .3s;max-width:380px;">
        <span id="toastTxt"></span>
    </div>
@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        // ── Toast ──────────────────────────────────────────────────────────────────
        function showToast(msg, ok=true) {
            const t = document.getElementById('toast');
            document.getElementById('toastTxt').textContent = msg;
            t.style.background = ok ? '#d1fae5' : '#fee2e2';
            t.style.color      = ok ? '#065f46' : '#7f1d1d';
            t.style.border     = ok ? '1px solid #6ee7b7' : '1px solid #fca5a5';
            t.style.transform  = 'translateY(0)'; t.style.opacity = '1';
            setTimeout(() => { t.style.transform = 'translateY(80px)'; t.style.opacity = '0'; }, 3200);
        }

        // ── Tabs ───────────────────────────────────────────────────────────────────
        function switchTab(name, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + name).classList.add('active');
        }

        // ── Generic helpers ────────────────────────────────────────────────────────
        async function apiPost(url, data) {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(data)
            });
            return res.json();
        }
        async function apiDelete(url) {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
            });
            return res.json();
        }

        // ─────────────── CHOFER ───────────────────────────────────────────────────
        async function guardarChofer() {
            const nombres = document.getElementById('cNombres').value.trim();
            if (!nombres) { showToast('Ingresa el nombre del chofer.', false); return; }
            const data = await apiPost('/catalogos/choferes', {
                nombres, apellido_paterno: document.getElementById('cApPaterno').value.trim(),
                apellido_materno: document.getElementById('cApMaterno').value.trim()
            });
            if (data.success) {
                showToast(data.message);
                ['cNombres','cApPaterno','cApMaterno'].forEach(id => document.getElementById(id).value = '');
                location.reload();
            } else showToast(data.message || 'Error', false);
        }

        let editCtx = null;
        function editarChofer(id, nombres, apP, apM) {
            editCtx = { tipo:'chofer', id };
            document.getElementById('modalEditTitle').textContent = 'Editar Chofer';
            document.getElementById('modalEditDesc').textContent  = nombres;
            document.getElementById('modalEditBody').innerHTML = `
        <div class="form-group" style="margin-bottom:12px;"><label class="form-label">Nombres *</label>
            <input type="text" id="eNombres" class="form-input" value="${nombres}"></div>
        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group"><label class="form-label">Ap. Paterno</label>
                <input type="text" id="eApP" class="form-input" value="${apP}"></div>
            <div class="form-group"><label class="form-label">Ap. Materno</label>
                <input type="text" id="eApM" class="form-input" value="${apM}"></div>
        </div>`;
            document.getElementById('modalEdit').classList.add('open');
        }

        // ─────────────── MAQUINARIA ───────────────────────────────────────────────
        async function guardarMaq() {
            const nombre = document.getElementById('mNombre').value.trim();
            if (!nombre) { showToast('Ingresa el nombre de la maquinaria.', false); return; }
            const data = await apiPost('/catalogos/maquinarias', {
                nombre, numero_maquina: document.getElementById('mNumero').value.trim()
            });
            if (data.success) { showToast(data.message); location.reload(); }
            else showToast(data.message || 'Error', false);
        }

        function editarMaq(id, nombre, num) {
            editCtx = { tipo:'maquinaria', id };
            document.getElementById('modalEditTitle').textContent = 'Editar Maquinaria';
            document.getElementById('modalEditDesc').textContent  = nombre;
            document.getElementById('modalEditBody').innerHTML = `
        <div class="form-group" style="margin-bottom:12px;"><label class="form-label">Nombre / Tipo *</label>
            <input type="text" id="eMaqNombre" class="form-input" value="${nombre}"></div>
        <div class="form-group"><label class="form-label">N° Máquina</label>
            <input type="text" id="eMaqNum" class="form-input" value="${num}"></div>`;
            document.getElementById('modalEdit').classList.add('open');
        }

        // ─────────────── AGREGADO ─────────────────────────────────────────────────
        async function guardarAgr() {
            const nombre = document.getElementById('aNombre').value.trim();
            if (!nombre) { showToast('Ingresa el nombre del agregado.', false); return; }
            const data = await apiPost('/catalogos/agregados', {
                nombre, numero_agregado: document.getElementById('aNumero').value.trim()
            });
            if (data.success) { showToast(data.message); location.reload(); }
            else showToast(data.message || 'Error', false);
        }

        function editarAgr(id, nombre, num) {
            editCtx = { tipo:'agregado', id };
            document.getElementById('modalEditTitle').textContent = 'Editar Agregado';
            document.getElementById('modalEditDesc').textContent  = nombre;
            document.getElementById('modalEditBody').innerHTML = `
        <div class="form-group" style="margin-bottom:12px;"><label class="form-label">Nombre *</label>
            <input type="text" id="eAgrNombre" class="form-input" value="${nombre}"></div>
        <div class="form-group"><label class="form-label">Código</label>
            <input type="text" id="eAgrNum" class="form-input" value="${num}"></div>`;
            document.getElementById('modalEdit').classList.add('open');
        }

        // ─────────────── SAVE EDIT ────────────────────────────────────────────────
        async function guardarEdit() {
            if (!editCtx) return;
            let url, payload;

            if (editCtx.tipo === 'chofer') {
                url = `/catalogos/choferes/${editCtx.id}`;
                payload = {
                    nombres: document.getElementById('eNombres').value.trim(),
                    apellido_paterno: document.getElementById('eApP').value.trim(),
                    apellido_materno: document.getElementById('eApM').value.trim(),
                };
            } else if (editCtx.tipo === 'maquinaria') {
                url = `/catalogos/maquinarias/${editCtx.id}`;
                payload = {
                    nombre:         document.getElementById('eMaqNombre').value.trim(),
                    numero_maquina: document.getElementById('eMaqNum').value.trim(),
                };
            } else {
                url = `/catalogos/agregados/${editCtx.id}`;
                payload = {
                    nombre:          document.getElementById('eAgrNombre').value.trim(),
                    numero_agregado: document.getElementById('eAgrNum').value.trim(),
                };
            }

            const data = await apiPost(url, { ...payload, _method: 'PUT' });
            if (data.success) {
                showToast(data.message);
                document.getElementById('modalEdit').classList.remove('open');
                location.reload();
            } else showToast(data.message || 'Error', false);
        }

        // ─────────────── DELETE ───────────────────────────────────────────────────
        let delCtx = null;
        function eliminarItem(tipo, id, nombre) {
            delCtx = { tipo, id };
            document.getElementById('modalDelDesc').textContent = `¿Eliminar "${nombre}"?`;
            document.getElementById('modalDel').classList.add('open');
        }
        document.getElementById('btnConfDel').addEventListener('click', async () => {
            if (!delCtx) return;
            const urlMap = { chofer: 'choferes', maquinaria: 'maquinarias', agregado: 'agregados' };
            const data = await apiDelete(`/catalogos/${urlMap[delCtx.tipo]}/${delCtx.id}`);
            document.getElementById('modalDel').classList.remove('open');
            if (data.success) { showToast('Eliminado correctamente.'); location.reload(); }
            else showToast('Error al eliminar.', false);
            delCtx = null;
        });

        ['modalEdit','modalDel'].forEach(id =>
            document.getElementById(id).addEventListener('click', e => {
                if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
            })
        );
    </script>
@endpush
