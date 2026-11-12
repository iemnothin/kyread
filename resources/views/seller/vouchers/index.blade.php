@extends('layouts.seller')

@section('title', 'Voucher Toko')
@section('breadcrumb')<li class="breadcrumb-item active">Voucher</li>@endsection

@section('content')
    <div class="card">
        <div class="card-body pt-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <p class="text-muted small mb-0">Voucher tampil di halaman toko dan detail buku. Pembeli memasukkan kodenya saat checkout; potongan hanya berlaku untuk buku dari tokomu.</p>
                <a class="btn btn-ky btn-sm ms-auto" href="{{ route('seller.vouchers.create') }}"><i class="bi bi-plus-lg me-1"></i>Buat Voucher</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Potongan</th>
                            <th>Masa berlaku</th>
                            <th class="text-center">Terpakai</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vouchers as $voucher)
                            @php($status = $voucher->status())
                            <tr>
                                <td><code class="fs-6">{{ $voucher->code }}</code></td>
                                <td class="small">{{ $voucher->summary }}</td>
                                <td class="small text-nowrap">
                                    {{ $voucher->starts_at?->translatedFormat('d M Y') ?? 'Sekarang' }} –
                                    {{ $voucher->expires_at?->translatedFormat('d M Y') ?? 'tanpa batas' }}
                                </td>
                                <td class="text-center">{{ $voucher->used_count }}{{ $voucher->quota ? ' / '.$voucher->quota : '' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $status === 'Aktif' ? 'bg-success' : ($status === 'Terjadwal' ? 'bg-info' : 'bg-secondary') }}">{{ $status }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('seller.vouchers.edit', $voucher) }}" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form class="d-inline" action="{{ route('seller.vouchers.destroy', $voucher) }}" method="POST" data-confirm="Hapus voucher {{ $voucher->code }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center text-muted py-5" colspan="6">
                                    <i class="bi bi-ticket-perforated fs-1 d-block mb-2"></i>
                                    Belum ada voucher. Buat voucher untuk menarik pembeli baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $vouchers->links() }}
        </div>
    </div>
@endsection
