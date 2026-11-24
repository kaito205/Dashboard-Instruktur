@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <!-- Filter Header Instruktur -->
    <x-dashboard.instruktur.filter-header
        :selected-period="$selectedPeriod"
        :selected-status="$selectedStatus ?? 'all'"
        :dari="$dari ?? null"
        :sampai="$sampai ?? null"
    />

    <!-- 4 Metric Cards Personal Instruktur -->
    <x-dashboard.instruktur.metrics-cards :metrics="$metrics" />

    <!-- Grafik Jam Mengajar Personal -->
    <x-dashboard.instruktur.personal-chart :chart-data="$personalChartData ?? null" />

    <!-- Grid Row: Jadwal Mengajar Saya Hari Ini & Feedback Peserta Terbaru -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-6 xl:col-span-6">
            <x-dashboard.instruktur.my-schedule
                :my-schedule="$mySchedule"
                :next-schedule="$nextSchedule ?? null"
                :today-label="$todayLabel ?? null"
            />
        </div>
        <div class="lg:col-span-6 xl:col-span-6">
            <x-dashboard.instruktur.student-feedback
                :student-feedback="$studentFeedback"
                :search="$search ?? ''"
                :selected-period="$selectedPeriod ?? 'bulan_ini'"
                :selected-status="$selectedStatus ?? 'all'"
            />
        </div>
    </div>
  </div>
@endsection

@push('scripts')
    <x-instruktur.gps-submit-script />
@endpush
