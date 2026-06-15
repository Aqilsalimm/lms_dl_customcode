<script setup>
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, CreditCard } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    invoice: {
        type: Object,
        required: true
    }
});

const formatPrice = (val) => {
    return parseFloat(val).toLocaleString('id-ID');
};

const handlePayment = () => {
    if (!props.invoice.midtrans_snap_token) {
        alert('Gagal mendapatkan token pembayaran.');
        return;
    }

    // Trigger Midtrans Snap Popup
    window.snap.pay(props.invoice.midtrans_snap_token, {
        onSuccess: function(result){
            // Optional: redirect to success page or refresh
            alert('Pembayaran berhasil!');
            window.location.reload();
        },
        onPending: function(result){
            alert('Menunggu pembayaran Anda.');
        },
        onError: function(result){
            alert('Pembayaran gagal.');
        },
        onClose: function(){
            alert('Anda menutup popup tanpa menyelesaikan pembayaran.');
        }
    });
};
</script>

<template>
    <Head title="Access Suspended | Drastha Learning" />

    <GuestLayout>
        <div class="min-h-[70vh] flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col items-center text-center relative overflow-hidden">
                
                <!-- Background decoration -->
                <div class="absolute top-0 left-0 w-full h-2 bg-red-500"></div>

                <!-- Icon -->
                <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mb-6 text-red-500">
                    <AlertTriangle :size="40" :stroke-width="2.5" />
                </div>

                <h2 class="text-2xl font-extrabold text-[#1A2B49] mb-3">Akses Ditangguhkan</h2>
                
                <p class="text-slate-500 text-sm md:text-base font-medium mb-8 leading-relaxed">
                    Mohon maaf, akses Anda ke kelas ini telah ditangguhkan karena adanya tagihan bulan ini yang belum diselesaikan. Silakan lakukan pembayaran untuk melanjutkan pembelajaran Anda.
                </p>

                <!-- Invoice Details -->
                <div class="w-full bg-slate-50 rounded-2xl p-6 mb-8 text-left border border-slate-100">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-4">Rincian Tagihan</h3>
                    
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-semibold text-slate-500">Nomor Tagihan</span>
                        <span class="text-sm font-extrabold text-[#1A2B49]">{{ invoice.invoice_number }}</span>
                    </div>

                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-semibold text-slate-500">Jatuh Tempo</span>
                        <span class="text-sm font-extrabold text-[#1A2B49]">{{ new Date(invoice.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                    </div>

                    <div class="w-full h-px bg-slate-200 my-3"></div>

                    <div class="flex justify-between items-center">
                        <span class="text-base font-bold text-slate-600">Total Tagihan</span>
                        <span class="text-xl font-black text-[#FF4D4F]">Rp {{ formatPrice(invoice.amount) }}</span>
                    </div>
                </div>

                <!-- Pay Button -->
                <button 
                    @click="handlePayment"
                    class="w-full bg-[#264790] hover:bg-[#1A2B49] text-white py-4 px-6 rounded-2xl font-bold text-sm md:text-base shadow-md transition-all flex items-center justify-center gap-2 hover:-translate-y-0.5"
                >
                    <CreditCard :size="20" />
                    Bayar Tagihan Sekarang
                </button>

            </div>
        </div>
    </GuestLayout>
</template>
