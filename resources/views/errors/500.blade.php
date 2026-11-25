@include('errors.layout', [
    'code'        => '500',
    'heading'     => 'Terjadi Gangguan pada Server',
    'description' => 'Permintaan Anda belum dapat diproses saat ini karena kendala teknis internal.',
    'hint'        => 'Silakan coba beberapa saat lagi atau kembali ke halaman utama dashboard.',
])
