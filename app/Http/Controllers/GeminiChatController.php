<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiChatController extends Controller
{
    /**
     * Proxy Gemini chat requests securely from the backend.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $apiKey = config('services.gemini.key');
        if (empty($apiKey)) {
            logger()->warning('Gemini API Proxy: API Key not configured. Using offline template fallback.');
            return response()->json([
                'reply' => $this->getTemplateReply($request->input('message')),
            ]);
        }

        try {
            $systemInstruction = "Anda adalah Asisten Virtual Support khusus untuk platform Drastha Learning. Tugas utama Anda adalah membantu calon peserta kursus yang tertarik untuk membeli kelas/kursus di platform kami.\n\nPERATURAN SANGAT KETAT:\n1. JAWAB HANYA pertanyaan seputar Drastha Learning (kelas yang ditawarkan, cara pendaftaran/pembelian, harga, metode pembayaran, sertifikat kelulusan, masa aktif kelas, dan jam operasional CS).\n2. Jika pengguna meminta Anda menyelesaikan/membuatkan kode program, debugging, menjelaskan konsep pemrograman teknis secara mendalam (misal meminta Anda memecahkan algoritma atau membuat script kode), atau menanyakan topik di luar pembelian kursus di Drastha Learning, Anda WAJIB MENOLAK secara halus. Contoh penolakan: 'Maaf, sebagai asisten support Drastha Learning, saya hanya dapat membantu menjawab pertanyaan seputar platform, informasi kelas, dan pembelian kursus kami. Untuk pertanyaan teknis/pemrograman, silakan diskusikan di forum diskusi kelas setelah Anda bergabung.'\n3. Bersikaplah ramah, santun, profesional, dan gunakan bahasa Indonesia yang baik. Jawab secara padat dan jelas untuk menghemat token.";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $request->input('message')],
                        ]
                    ]
                ],
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 250,
                    'temperature' => 0.7,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return response()->json([
                        'reply' => $data['candidates'][0]['content']['parts'][0]['text'],
                    ]);
                }
            }

            logger()->error('Gemini API response error or structure mismatched. Response: ' . $response->body());
            return response()->json([
                'reply' => $this->getTemplateReply($request->input('message')),
            ]);

        } catch (\Exception $e) {
            logger()->error('Gemini API Proxy Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => $this->getTemplateReply($request->input('message')),
            ]);
        }
    }

    /**
     * Offline template database for offline/error fallback replies.
     */
    private function getTemplateReply(string $message): string
    {
        $msg = strtolower($message);
        
        if (str_contains($msg, 'halo') || str_contains($msg, 'hi') || str_contains($msg, 'pagi') || str_contains($msg, 'siang') || str_contains($msg, 'sore')) {
            return "Halo! Saya adalah AI Admin Drastha Learning. Ada yang bisa saya bantu mengenai platform pembelajaran kami?";
        }
        if (str_contains($msg, 'beli') || str_contains($msg, 'daftar kelas') || str_contains($msg, 'cara mendaftar') || str_contains($msg, 'membeli')) {
            return "Untuk membeli kelas, silakan navigasikan ke menu 'Pilihan Kelas', pilih kelas yang Anda inginkan, klik 'Beli Sekarang' atau 'Tambah ke Keranjang', lalu lakukan checkout dan pembayaran melalui metode yang tersedia.";
        }
        if (str_contains($msg, 'bayar') || str_contains($msg, 'metode') || str_contains($msg, 'rekening') || str_contains($msg, 'transfer')) {
            return "Drastha Learning mendukung berbagai metode pembayaran otomatis, termasuk Virtual Account (VA), Transfer Bank, e-Wallet (GoPay, OVO, Dana), dan Kartu Kredit.";
        }
        if (str_contains($msg, 'sertifikat') || str_contains($msg, 'klaim') || str_contains($msg, 'unduh sertifikat')) {
            return "Sertifikat kelulusan dapat Anda unduh setelah menyelesaikan seluruh materi kelas, lulus kuis akhir, dan mengirim tugas jika diwajibkan. Anda dapat mengunduhnya di tab 'Sertifikat' di halaman detail kursus Anda.";
        }
        if (str_contains($msg, 'jam') || str_contains($msg, 'operasional') || str_contains($msg, 'cs') || str_contains($msg, 'customer service') || str_contains($msg, 'buka')) {
            return "Customer Service kami beroperasi pada hari Senin - Sabtu pukul 08:00 - 17:00 WIB. Anda dapat menghubungi mereka secara langsung melalui tombol WhatsApp atau Email di menu Layanan Support.";
        }
        if (str_contains($msg, 'durasi') || str_contains($msg, 'selamanya') || str_contains($msg, 'masa aktif') || str_contains($msg, 'lisensi')) {
            return "Masa aktif akses kelas bergantung pada paket yang dibeli (misalnya 1 bulan, 3 bulan, 6 bulan, atau Akses Selamanya/Lifetime) seperti yang tertera pada bagian detail kelas.";
        }

        return "Maaf, saya tidak mengerti pertanyaan Anda. Apakah Anda ingin menanyakan tentang cara membeli kelas, metode pembayaran, sertifikat, atau jam operasional Customer Service?";
    }
}
