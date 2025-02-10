<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use TCPDF;
use App\Models\OzelSertifika;
class CertificateController extends Controller
{
    
    
     public function generateCertificate()
    {
        // Kullanıcının sertifika bilgilerini al
        $userId = auth()->id(); // Giriş yapmış kullanıcının ID'si
        $row = OzelSertifika::where('user_id', $userId)->first();

        if (!$row) {
            return response()->json(['error' => 'Sertifika verisi bulunamadı'], 404);
        }

        // Sertifika şablonu seçimi değerine göre farklı fonksiyonlar çağır
        switch ($row->sertifika_sablonu_secimi) {
            case 'Tam Kaplayan':
                $this->generateFullCoverCertificate($row); // 1. fonksiyon
                break;

            case 'Ortalanmış':
                $this->generateCenteredCertificate($row); // 2. fonksiyon
                break;

            case 'Küçük':
                $this->generateSmallCertificate($row); // 3. fonksiyon
                break;

            default:
                return response()->json(['error' => 'Geçersiz şablon seçimi'], 400);
        }
    }
    
    
    public function generateFullCoverCertificate()
    {
        $userId = auth()->id(); // Auth sınıfı ile giriş yapan kullanıcının ID'sini alıyoruz
        $data = DB::table('ilkyardim')
            ->leftJoin('company_details', 'ilkyardim.user_id', '=', 'company_details.user_id') // company_details ile join
            ->leftJoin('ozel_sertifikas', 'ilkyardim.user_id', '=', 'ozel_sertifikas.user_id') // ozel_sertifikas ile join
            ->where('ilkyardim.user_id', $userId)
            ->select(
                'ilkyardim.*', 
                'company_details.company_name', 
                'company_details.logo_path', 
                'ozel_sertifikas.*' // ozel_sertifikas tablosunun tüm sütunlarını seçiyoruz
            )
            ->get();


        // Yeni bir PDF belgesi oluştur
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(0, 0, 0);  // Sol, üst, sağ kenar boşluklarını sıfırla
        $pdf->SetAutoPageBreak(false); // Sayfa kırılmasını kapat

        foreach ($data as $row) {
            $pdf->AddPage();

            // Arka plan görseli
            if($row->sertifika_sablonu_secimi === "Tam Kaplayan"){
            $imageFile = public_path('images/sertifika.jpg');
            }elseif($row->sertifika_sablonu_secimi === "Ortalanmış"){
              $imageFile = public_path('images/Ortalanmis.jpg');  
            }elseif($row->sertifika_sablonu_secimi === "Küçük"){
                $imageFile = public_path('images/Kucuk.jpg');
            }
            
            $pdf->Image($imageFile, 0, 0, 297, 210); // Resmin boyutları ayarlandı (Yatay A4 boyutları)



            if($row->logo_konumu === "Sağ"){
            // Logo ekleme
            $logoPath = public_path($row->logo_path); // logo_path veritabanından alınıyor
            if (file_exists($logoPath)) {
                // Logoyu 50x50 mm boyutunda ekliyoruz
                if($row->logo_sekli === "Daire"){
                    $pdf->Image($logoPath, 243, 25, 30, 30);
                }elseif($row->logo_sekli === "Kare"){
                    $pdf->Image($logoPath, 243, 25, 30, 30); 
                }elseif($row->logo_sekli === "Dikdörtgen"){
                    $pdf->Image($logoPath, 215, 25, 60, 30); 
                }
                
            } else {
                $pdf->SetXY(10, 10);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, 'Logo Dosyası Bulunamadı', 0, 1, 'L');
            }
    
            }
            if($row->logo_konumu === "Orta"){
                $logoPath = public_path($row->logo_path);
                if (file_exists($logoPath)) {

                    
                if($row->logo_sekli === "Daire"){
                    $textWidth = 30; 
                    $centeredX = 237 - ($textWidth / 2);
                    $pdf->Image($logoPath, $centeredX, 25, 30, 30);
                }elseif($row->logo_sekli === "Kare"){
                    $textWidth = 30; 
                    $centeredX = 237 - ($textWidth / 2);
                    $pdf->Image($logoPath, $centeredX, 25, 30, 30); 
                }elseif($row->logo_sekli === "Dikdörtgen"){
                    $textWidth = 60; 
                    $centeredX = 237 - ($textWidth / 2);
                    $pdf->Image($logoPath, $centeredX, 25, 60, 30); 
                }
                }else{
                $pdf->SetXY(10, 10);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, '', 0, 1, 'L');
                }
                
            }
            if($row->logo_konumu === "Sol"){
                $logoPath = public_path($row->logo_path);
                if (file_exists($logoPath)) {
                    $textWidth = 30; 
                    $centeredX = 237 - ($textWidth / 2);
                if($row->logo_sekli === "Daire"){
                    $pdf->Image($logoPath, 200, 25, 30, 30);
                }elseif($row->logo_sekli === "Kare"){
                    $pdf->Image($logoPath, 200, 25, 30, 30);
                }elseif($row->logo_sekli === "Dikdörtgen"){
                    $pdf->Image($logoPath, 190, 25, 60, 30); 
                }
                    
                }else{
                $pdf->SetXY(10, 10);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, '', 0, 1, 'L');
                }
            }




    

            // TCPDF'nin eklediği fontu kullan
            $pdf->SetFont('ttimesb', '', 12); // Burada 'times' font adı, yüklenen fontun adı
            

            
            $text = $row->Üst_metin_1;
            $pdf->SetFont('ttimesb', '', 16);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 28);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');


            
            $text = $row->Üst_metin_2;
            $pdf->SetFont('ttimesb', '', 16);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 35);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');


            $text = $row->Üst_metin_3;
            $pdf->SetFont('ttimesb', '', 16);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 42);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');



            $text = "İLKYARDIMCI BELGESİ";
            $pdf->SetFont('ttimesb', '', 18);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 85);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');


            $pdf->SetXY(38, 64);
            $pdf->SetFont('times2', '', 16);
            $pdf->Cell(0, 10, "Belge Tipi / No", 0, 1, 'L');
            
            $pdf->SetXY(96, 64);
            $pdf->SetFont('times2', '', 16);
            $pdf->Cell(0, 10, ":", 0, 1, 'L');

            $pdf->SetXY(38, 73);
            $pdf->SetFont('times2', '', 16);
            $pdf->Cell(0, 10, "Belge Geçerlilik Tarihi", 0, 1, 'L');

            $pdf->SetXY(96, 73);
            $pdf->SetFont('times2', '', 16);
            $pdf->Cell(0, 10, ":", 0, 1, 'L');
            // Belge No
            $pdf->SetXY(98, 64);
            $pdf->SetFont('ttimesb', '', 17);
            $pdf->Cell(0, 10, $row->ilkyardim_belge_no, 0, 1, 'L');

            // Belge Geçerlilik Tarihi
            $pdf->SetXY(98, 73);
            $pdf->SetFont('ttimesb', '', 17);
            $pdf->Cell(0, 10, $this->formatDate($row->ilkyardim_belge_gecerlilik_tarihi), 0, 1, 'L');
            
            // Belge Geçerlilik Tarihi
            $pdf->SetXY(88, 95);
            $pdf->SetFont('ttimesb', '', 17);
            $pdf->Cell(0, 10, "Sayın;", 0, 1, 'L');
            
            $text = $row->katilimci_adi_soyadi;
            $pdf->SetFont('ttimesb', '', 16);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 95);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');

            $pdf->SetXY(30, 105); // Yeni bir konum
            $pdf->SetFont('times2', '', 16);
            $pdf->Cell(0, 10, "İlkyardım yönetmeliği kapsamında", 0, 1, 'L');

            // Eğitim Başlama Tarihi
            $pdf->SetXY(116, 105); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 16);
            $pdf->Cell(0, 10, $this->formatDate($row->egitim_baslama), 0, 1, 'L');

            // Eğitim Başlama Tarihi
            $pdf->SetXY(147, 105); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 18);
            $pdf->Cell(0, 10, "-", 0, 1, 'L');


            // Eğitim Bitiş Tarihi
            $pdf->SetXY(150, 105);
            $pdf->SetFont('ttimesb', '', 16);
            $pdf->Cell(0, 10, $this->formatDate($row->egitim_bitis), 0, 1, 'L');


            $pdf->SetXY(178, 105); // Yeni bir konum
            $pdf->SetFont('times2', '', 16);
            $pdf->Cell(0, 10, "tarihleri arasında", 0, 1, 'L');


            // Company Name (First two words)
            $companyName = $row->company_name;
            $companyNameParts = explode(' ', $companyName); // company_name'ı boşluklardan ayırıyoruz
            $firstTwoWords = implode(' ', array_slice($companyNameParts, 0, 2)); 


            $pdf->SetXY(218, 105); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 16);
            $pdf->Cell(0, 10, $firstTwoWords, 0, 1, 'L');

            $pdf->SetXY(30, 115); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 16);
            $pdf->Cell(0, 10, "İLKYARDIM EĞİTİM MERKEZİ", 0, 1, 'L');

            $pdf->SetXY(120, 115); // Yeni bir konum
            $pdf->SetFont('times2', '', 16);
            $pdf->Cell(0, 10, "tarafından düzenlenen", 0, 1, 'L');

            $pdf->SetXY(175, 115);
            $pdf->SetFont('ttimesb', '', 16);
            $pdf->Cell(0, 10, $row->egitim_turu, 0, 1, 'L');

            if ($row->egitim_turu === "İlkyardım Eğitimi"){

                $pdf->SetXY(223, 115); // Yeni bir konum
                $pdf->SetFont('times2', '', 16);
                $pdf->Cell(0, 10, "eğitim programını", 0, 1, 'L');

                $pdf->SetXY(30, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16);
                $pdf->Cell(0, 10, "başarı ile bitirerek", 0, 1, 'L');

                $pdf->SetXY(75, 125); // Yeni bir konum
                $pdf->SetFont('ttimesb', '', 16);
                $pdf->Cell(0, 10, "İLKYARDIMCI", 0, 1, 'L');

                $pdf->SetXY(118, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16);
                $pdf->Cell(0, 10, "olmaya hak kazanmıştır.", 0, 1, 'L');
            }

            if ($row->egitim_turu === "İlkyardım Eğitimi (Gün.)"){
                $pdf->SetXY(238, 115); // Yeni bir konum
                $pdf->SetFont('times2', '', 16);
                $pdf->Cell(0, 10, "eğitim", 0, 1, 'L');
                
                $pdf->SetXY(30, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16);
                $pdf->Cell(0, 10, "programına katılarak", 0, 1, 'L');
                
                $pdf->SetXY(80, 125); // Yeni bir konum
                $pdf->SetFont('ttimesb', '', 16);
                $pdf->Cell(0, 10, "İLKYARDIMCI", 0, 1, 'L');
                
                $pdf->SetXY(122, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16);
                $pdf->Cell(0, 10, "belgesi süresi uzatılmıştır.", 0, 1, 'L');
            }


            $pdf->SetXY(62, 178);
            $pdf->SetFont('times2', '', 10);
            $pdf->Cell(0, 10, "Belgeyi Doğrulamak İçin:", 0, 1, 'L');

            // Doğrulama Kodu
            $pdf->SetXY(100, 178);
            $pdf->SetFont('ttimesb', '', 10);
            $pdf->Cell(0, 10, $row->dogrulama_kodu, 0, 1, 'L');
            
            
$yuzde9 = 0;
$yuzde916 =0;
$deger10 = 0;
$deger5 = 0;
$yuzde918 =0;
$yuzde917 =0;
$deger1 = 0;
$deger6 = 0;
$deger22 = 0;

if($row->alt_kac_bolum === "3") {
    if($row->unvan_ve_isimler_konumu === "Üst"){
    
        $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 140.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 146);
        }else{
            $pdf->SetXY($centeredX, 132);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 140.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 140.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    }elseif ($row->unvan_ve_isimler_konumu === "Orta") {
    
    
        $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158.5);
        }else{
            $pdf->SetXY($centeredX, 146.5);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 150.5);
        }else{
            $pdf->SetXY($centeredX, 159.5);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 153.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 159.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $pdf->SetFont('times2', '', 16-$yuzde916);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153.5);
        }else{
            $pdf->SetXY($centeredX, 159.5);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 153.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 159.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153.5);
        }else{
            $pdf->SetXY($centeredX, 159.5);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 153.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }else{
    
    
        $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 172.5);
        }else{
            $pdf->SetXY($centeredX, 158.5);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 165.5);
        }else{
            $pdf->SetXY($centeredX, 171);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158.5);
        }else{
            $pdf->SetXY($centeredX, 165);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 172.5);
        }else{
            $pdf->SetXY($centeredX, 158.5);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 165.5);
        }else{
            $pdf->SetXY($centeredX, 171);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158.5);
        }else{
            $pdf->SetXY($centeredX, 165);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 172.5);
        }else{
            $pdf->SetXY($centeredX, 158.5);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 165.5);
        }else{
            $pdf->SetXY($centeredX, 171);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 18;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 16;
                $fontek = 18;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60   - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158.5);
        }else{
            $pdf->SetXY($centeredX, 165);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }
    
    }
            
            
if($row->alt_kac_bolum === "2") {

if($row->unvan_ve_isimler_konumu === "Üst"){

  // Sağ İsim
  $text = $row->sag_taraf_isim;
  if ($row->isimler_kalin_mi === "Kalın") {
      $fontSize = 18;
      $fontek = 0;
      if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
          $fontSize = 16;
          $fontek = 18;
      }
      $pdf->SetFont('ttimesb', '', $fontSize);
  }else{

      $fontSize = 18;
      $fontek = 0;
      if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
          $fontSize = 16;
          $fontek = 18;
      }
      $pdf->SetFont('times2', '', $fontSize);
  }
  $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
  $centeredX = 220 - ($textWidth / 2);
  if($row->unvan_konumu === "Üst"){
      $pdf->SetXY($centeredX, 147);
  }else{
      $pdf->SetXY($centeredX, 133);
  } // Yatayda X = 60'a ortalamak için X konumunu ayarla
  $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sag_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 220  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 140);
} else {
    $pdf->SetXY($centeredX, 147);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


////
$text2 = $row->sag_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 220  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 133);
} else {
    $pdf->SetXY($centeredX, 140.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır



// Sol İsim
$text = $row->sol_taraf_isim;
if ($row->isimler_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 70  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 147);
} else {
    $pdf->SetXY($centeredX, 133);
} // Yatayda X = 60'a ortalamak için X konumunu ayarla
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sol_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 70  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 140);
} else {
    $pdf->SetXY($centeredX, 147);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


$text2 = $row->sol_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 70  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 133);
} else {
    $pdf->SetXY($centeredX, 140.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır



}elseif ($row->unvan_ve_isimler_konumu === "Orta") {


    
    $text = $row->sag_taraf_isim;
    if ($row->isimler_kalin_mi === "Kalın") {
        $fontSize = 18;
        $fontek = 0;
        if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
            $fontSize = 16;
            $fontek = 18;
        }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{

        $fontSize = 18;
        $fontek = 0;
        if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
            $fontSize = 16;
            $fontek = 18;
        }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
    $centeredX = 220 - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 153.5);
    }else{
        $pdf->SetXY($centeredX, 147.5);
    } // Yatayda X = 60'a ortalamak için X konumunu ayarla
    $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////
$text = $row->sag_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 220  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 153.5);
} else {
    $pdf->SetXY($centeredX, 159.5);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


//
$text2 = $row->sag_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 220  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 147.5);
} else {
    $pdf->SetXY($centeredX, 153.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır



/// SAĞ İSİM




    $text = $row->sol_taraf_isim;
    if ($row->isimler_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
    $centeredX = 70  - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 159.5);
    }else{
        $pdf->SetXY($centeredX, 147);
    } // Yatayda X = 60'a ortalamak için X konumunu ayarla
    $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

    ////
    $text = $row->sol_taraf_unvan;
    if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
    $centeredX = 70  - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 153.5);
    }else{
        $pdf->SetXY($centeredX, 159.5);
    }
    $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
    
    
    $text2 = $row->sol_taraf_unvan2;
    if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
    $centeredX = 70  - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 147);
    }else{
        $pdf->SetXY($centeredX, 153.5);
    }
    $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır

}else{


    $text = $row->sag_taraf_isim;
    if ($row->isimler_kalin_mi === "Kalın") {
        $fontSize = 18;
        $fontek = 0;
        if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
            $fontSize = 16;
            $fontek = 18;
        }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{

        $fontSize = 18;
        $fontek = 0;
        if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
            $fontSize = 16;
            $fontek = 18;
        }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
    $centeredX = 220 - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 147);
    }else{
        $pdf->SetXY($centeredX, 133);
    } // Yatayda X = 60'a ortalamak için X konumunu ayarla
    $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sag_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 220  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 165.5);
} else {
    $pdf->SetXY($centeredX, 172.5);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır



$text2 = $row->sag_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 220  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 158.5);
} else {
    $pdf->SetXY($centeredX, 165.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır




// Sol İsim
$text = $row->sol_taraf_isim;
if ($row->isimler_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 70  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 172.5);
} else {
    $pdf->SetXY($centeredX, 158.5);
} // Yatayda X = 60'a ortalamak için X konumunu ayarla
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sol_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 70  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 165.5);
} else {
    $pdf->SetXY($centeredX, 172.5);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır





$text2 = $row->sol_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 18;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 16;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 70  - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 158.5);
} else {
    $pdf->SetXY($centeredX, 165.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır




}
            }
            
        }

        // PDF çıktısını çıktı olarak ver
        $pdf->Output('sertifikalar.pdf', 'I');
    }
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//////
    public function generateCenteredCertificate()
    {
        $userId = auth()->id(); // Auth sınıfı ile giriş yapan kullanıcının ID'sini alıyoruz
        $data = DB::table('ilkyardim')
            ->leftJoin('company_details', 'ilkyardim.user_id', '=', 'company_details.user_id') // company_details ile join
            ->leftJoin('ozel_sertifikas', 'ilkyardim.user_id', '=', 'ozel_sertifikas.user_id') // ozel_sertifikas ile join
            ->where('ilkyardim.user_id', $userId)
            ->select(
                'ilkyardim.*', 
                'company_details.company_name', 
                'company_details.logo_path', 
                'ozel_sertifikas.*' // ozel_sertifikas tablosunun tüm sütunlarını seçiyoruz
            )
            ->get();


        // Yeni bir PDF belgesi oluştur
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetMargins(0, 0, 0);  // Sol, üst, sağ kenar boşluklarını sıfırla
        $pdf->SetAutoPageBreak(false); // Sayfa kırılmasını kapat

        foreach ($data as $row) {
            $pdf->AddPage();

            // Arka plan görseli
            if($row->sertifika_sablonu_secimi === "Tam Kaplayan"){
            $imageFile = public_path('images/sertifika.jpg');
            }elseif($row->sertifika_sablonu_secimi === "Ortalanmış"){
              $imageFile = public_path('images/Ortalanmis.jpg');  
            }elseif($row->sertifika_sablonu_secimi === "Küçük"){
                $imageFile = public_path('images/Kucuk.jpg');
            }
            
            $pdf->Image($imageFile, 0, 0, 297, 210); // Resmin boyutları ayarlandı (Yatay A4 boyutları)


$yuzde9 = 0.009;
$yuzde916 =1.44;
$deger10 = 10;
$deger5 = 5;
$yuzde918 =1.62;
$yuzde917 =1.53;
$deger1 = 1;
$deger6 = 6;
$deger22 = 22;
            if($row->logo_konumu === "Sağ"){
            // Logo ekleme
            $logoPath = public_path($row->logo_path); // logo_path veritabanından alınıyor
            if (file_exists($logoPath)) {
                // Logoyu 50x50 mm boyutunda ekliyoruz
                if($row->logo_sekli === "Daire"){
                    $pdf->Image($logoPath, 223 + $deger10, 25+ $deger10, 25, 25);
                }elseif($row->logo_sekli === "Kare"){
                    $pdf->Image($logoPath, 243- $deger10, 25+ $deger10, 25, 25); 
                }elseif($row->logo_sekli === "Dikdörtgen"){
                    $pdf->Image($logoPath, 215- $deger10, 25+ $deger10, 54, 25); 
                }
                
            } else {
                $pdf->SetXY(10, 10);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, 'Logo Dosyası Bulunamadı', 0, 1, 'L');
            }
    
            }
            if($row->logo_konumu === "Orta"){
                $logoPath = public_path($row->logo_path);
                if (file_exists($logoPath)) {

                    
                if($row->logo_sekli === "Daire"){
                    $textWidth = 30; 
                    $centeredX = 237 - ($textWidth / 2);
                    $pdf->Image($logoPath, $centeredX- $deger10, 25+ $deger10, 25, 25);
                }elseif($row->logo_sekli === "Kare"){
                    $textWidth = 30; 
                    $centeredX = 237 - ($textWidth / 2);
                    $pdf->Image($logoPath, $centeredX- $deger10, 25+ $deger10, 25, 25); 
                }elseif($row->logo_sekli === "Dikdörtgen"){
                    $textWidth = 60; 
                    $centeredX = 237 - ($textWidth / 2);
                    $pdf->Image($logoPath, $centeredX- $deger10, 25+ $deger10, 54, 25); 
                }
                }else{
                $pdf->SetXY(10, 10);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, '', 0, 1, 'L');
                }
                
            }
            if($row->logo_konumu === "Sol"){
                $logoPath = public_path($row->logo_path);
                if (file_exists($logoPath)) {
                    $textWidth = 30; 
                    $centeredX = 237 - ($textWidth / 2);
                if($row->logo_sekli === "Daire"){
                    $pdf->Image($logoPath, 200- $deger10, 25+ $deger10, 25, 25);
                }elseif($row->logo_sekli === "Kare"){
                    $pdf->Image($logoPath, 200- $deger10, 25+ $deger10, 25, 25);
                }elseif($row->logo_sekli === "Dikdörtgen"){
                    $pdf->Image($logoPath, 190- $deger10, 25+ $deger10, 54, 25); 
                }
                    
                }else{
                $pdf->SetXY(10, 10);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, '', 0, 1, 'L');
                }
            }




    

            // TCPDF'nin eklediği fontu kullan
            $pdf->SetFont('ttimesb', '', 12); // Burada 'times' font adı, yüklenen fontun adı
            

            
            $text = $row->Üst_metin_1;
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 28+$deger5);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');


            
            $text = $row->Üst_metin_2;
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 35+$deger5);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');


            $text = $row->Üst_metin_3;
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 42+$deger5);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');



            $text = "İLKYARDIMCI BELGESİ";
            $pdf->SetFont('ttimesb', '', 18-$yuzde918);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 85);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');



            $pdf->SetXY(38+$deger10, 64);
            $pdf->SetFont('times2', '', 16-$yuzde916);
            $pdf->Cell(0, 10, "Belge Tipi / No", 0, 1, 'L');
            
            $pdf->SetXY(96+$deger10, 64);
            $pdf->SetFont('times2', '', 16-$yuzde916);
            $pdf->Cell(0, 10, ":", 0, 1, 'L');

            $pdf->SetXY(38+$deger10, 73);
            $pdf->SetFont('times2', '', 16-$yuzde916);
            $pdf->Cell(0, 10, "Belge Geçerlilik Tarihi", 0, 1, 'L');

            $pdf->SetXY(96+$deger10, 73);
            $pdf->SetFont('times2', '', 16-$yuzde916);
            $pdf->Cell(0, 10, ":", 0, 1, 'L');
            // Belge No
            $pdf->SetXY(98+$deger10, 64);
            $pdf->SetFont('ttimesb', '', 17-$yuzde917);
            $pdf->Cell(0, 10, $row->ilkyardim_belge_no, 0, 1, 'L');

            // Belge Geçerlilik Tarihi
            $pdf->SetXY(98+$deger10, 73);
            $pdf->SetFont('ttimesb', '', 17-$yuzde917);
            $pdf->Cell(0, 10, $this->formatDate($row->ilkyardim_belge_gecerlilik_tarihi), 0, 1, 'L');
            
            // Belge Geçerlilik Tarihi
            $pdf->SetXY(88-$deger5, 95);
            $pdf->SetFont('ttimesb', '', 17-$yuzde917);
            $pdf->Cell(0, 10, "Sayın;", 0, 1, 'L');
            
            $text = $row->katilimci_adi_soyadi;
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $textWidth = $pdf->GetStringWidth($text);
            $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
            $pdf->SetXY($centeredX, 95);
            $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');

            $pdf->SetXY(30+$deger10, 105); // Yeni bir konum
            $pdf->SetFont('times2', '', 16-$yuzde916);
            $pdf->Cell(0, 10, "İlkyardım yönetmeliği kapsamında", 0, 1, 'L');

            // Eğitim Başlama Tarihi
            $pdf->SetXY(116-$deger1, 105); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $pdf->Cell(0, 10, $this->formatDate($row->egitim_baslama), 0, 1, 'L');

            // Eğitim Başlama Tarihi
            $pdf->SetXY(147-$deger5, 105); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 18);
            $pdf->Cell(0, 10, "-", 0, 1, 'L');


            // Eğitim Bitiş Tarihi
            $pdf->SetXY(150-$deger5, 105);
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $pdf->Cell(0, 10, $this->formatDate($row->egitim_bitis), 0, 1, 'L');


            $pdf->SetXY(178-$deger5, 105); // Yeni bir konum
            $pdf->SetFont('times2', '', 16-$yuzde916);
            $pdf->Cell(0, 10, "tarihleri arasında", 0, 1, 'L');


            // Company Name (First two words)
            $companyName = $row->company_name;
            $companyNameParts = explode(' ', $companyName); // company_name'ı boşluklardan ayırıyoruz
            $firstTwoWords = implode(' ', array_slice($companyNameParts, 0, 2)); 


            $pdf->SetXY(218-$deger5, 105); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $pdf->Cell(0, 10, $firstTwoWords, 0, 1, 'L');

            $pdf->SetXY(30+$deger10, 115); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $pdf->Cell(0, 10, "İLKYARDIM EĞİTİM MERKEZİ", 0, 1, 'L');

            $pdf->SetXY(120+$deger5, 115); // Yeni bir konum
            $pdf->SetFont('times2', '', 16-$yuzde916);
            $pdf->Cell(0, 10, "tarafından düzenlenen", 0, 1, 'L');

            $pdf->SetXY(175-$deger1, 115);
            $pdf->SetFont('ttimesb', '', 16-$yuzde916);
            $pdf->Cell(0, 10, $row->egitim_turu, 0, 1, 'L');

            if ($row->egitim_turu === "İlkyardım Eğitimi"){

                $pdf->SetXY(223-$deger5, 115); // Yeni bir konum
                $pdf->SetFont('times2', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "eğitim programını", 0, 1, 'L');

                $pdf->SetXY(30+$deger10, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "başarı ile bitirerek", 0, 1, 'L');

                $pdf->SetXY(75+$deger6, 125); // Yeni bir konum
                $pdf->SetFont('ttimesb', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "İLKYARDIMCI", 0, 1, 'L');

                $pdf->SetXY(118+$deger5, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "olmaya hak kazanmıştır.", 0, 1, 'L');
            }

            if ($row->egitim_turu === "İlkyardım Eğitimi (Gün.)"){
                $pdf->SetXY(238-$deger5, 115); // Yeni bir konum
                $pdf->SetFont('times2', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "eğitim", 0, 1, 'L');
                
                $pdf->SetXY(30+$deger10, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "programına katılarak", 0, 1, 'L');
                
                $pdf->SetXY(80+$deger6, 125); // Yeni bir konum
                $pdf->SetFont('ttimesb', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "İLKYARDIMCI", 0, 1, 'L');
                
                $pdf->SetXY(122+$deger5, 125); // Yeni bir konum
                $pdf->SetFont('times2', '', 16-$yuzde916);
                $pdf->Cell(0, 10, "belgesi süresi uzatılmıştır.", 0, 1, 'L');
            }




if($row->dogrulama_linki_konumu === "İçerde"){
            $pdf->SetXY(62, 178-$deger5);
            $pdf->SetFont('times2', '', 10);
            $pdf->Cell(0, 10, "Belgeyi Doğrulamak İçin:", 0, 1, 'L');
              // Doğrulama Kodu
            $pdf->SetXY(100, 178-$deger5);
            $pdf->SetFont('ttimesb', '', 10);
            $pdf->Cell(0, 10, $row->dogrulama_kodu, 0, 1, 'L');  
}else{
            $pdf->SetXY(62, 178+$deger22);
            $pdf->SetFont('times2', '', 10);
            $pdf->Cell(0, 10, "Belgeyi Doğrulamak İçin:", 0, 1, 'L');
            // Doğrulama Kodu
            $pdf->SetXY(100, 178+$deger22);
            $pdf->SetFont('ttimesb', '', 10);
            $pdf->Cell(0, 10, $row->dogrulama_kodu, 0, 1, 'L');
}

            
            
            
if($row->alt_kac_bolum === "3") {
    if($row->unvan_ve_isimler_konumu === "Üst"){
    
        $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 143);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text2 = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 138);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
        
        
        
        

        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 138);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 143);
        }else{
            $pdf->SetXY($centeredX, 133);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 138);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 138);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 143);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 138);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 138);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    }elseif ($row->unvan_ve_isimler_konumu === "Orta") {
    
    
           $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 143);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 148);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 143);
        }else{
            $pdf->SetXY($centeredX, 148);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $pdf->SetFont('times2', '', 16-$yuzde916);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 148);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 143);
        }else{
            $pdf->SetXY($centeredX, 148);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 143);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 148);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 143);
        }else{
            $pdf->SetXY($centeredX, 148);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }else{
    
    
   $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }
    
    }
            
            
if($row->alt_kac_bolum === "2") {

if($row->unvan_ve_isimler_konumu === "Üst"){

  $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 220 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sag_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 220 - $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 140.5);
} else {
    $pdf->SetXY($centeredX, 147);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


////
$text2 = $row->sag_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 220 - $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 133);
} else {
    $pdf->SetXY($centeredX, 140.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır



// Sol İsim
$text = $row->sol_taraf_isim;
if ($row->isimler_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 65 + $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 147);
} else {
    $pdf->SetXY($centeredX, 133);
} // Yatayda X = 60'a ortalamak için X konumunu ayarla
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sol_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 65 + $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 140.5);
} else {
    $pdf->SetXY($centeredX, 147);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


$text2 = $row->sol_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 65 + $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 133);
} else {
    $pdf->SetXY($centeredX, 140.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır



}elseif ($row->unvan_ve_isimler_konumu === "Orta") {


    
  $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 220 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 159.5);
        }else{
            $pdf->SetXY($centeredX, 147.5);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////
$text = $row->sag_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 220 - $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 153.5);
} else {
    $pdf->SetXY($centeredX, 159.5);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


//
$text2 = $row->sag_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 220 - $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 147.5);
} else {
    $pdf->SetXY($centeredX, 153.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır



/// SAĞ İSİM




    $text = $row->sol_taraf_isim;
    if ($row->isimler_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
    $centeredX = 65 + $fontek - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 159.5);
    }else{
        $pdf->SetXY($centeredX, 147);
    } // Yatayda X = 60'a ortalamak için X konumunu ayarla
    $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

    ////
    $text = $row->sol_taraf_unvan;
    if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
    $centeredX = 65 + $fontek - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 153.5);
    }else{
        $pdf->SetXY($centeredX, 159.5);
    }
    $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
    
    
    $text2 = $row->sol_taraf_unvan2;
    if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
        $pdf->SetFont('ttimesb', '', $fontSize);
    }else{
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
        $pdf->SetFont('times2', '', $fontSize);
    }
    $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
    $centeredX = 65 + $fontek - ($textWidth / 2);
    if($row->unvan_konumu === "Üst"){
        $pdf->SetXY($centeredX, 147.5);
    }else{
        $pdf->SetXY($centeredX, 153.5);
    }
    $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır

}else{


  $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 16 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 14 -  $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 220 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 172.5);
        }else{
            $pdf->SetXY($centeredX, 158.5);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sag_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 220 - $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 165.5);
} else {
    $pdf->SetXY($centeredX, 172.5);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır



$text2 = $row->sag_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 220 - $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 158.5);
} else {
    $pdf->SetXY($centeredX, 165.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır




// Sol İsim
$text = $row->sol_taraf_isim;
if ($row->isimler_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 65 + $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 172.5);
} else {
    $pdf->SetXY($centeredX, 158.5);
} // Yatayda X = 60'a ortalamak için X konumunu ayarla
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

////

$text = $row->sol_taraf_unvan;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
$centeredX = 65 + $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 165.5);
} else {
    $pdf->SetXY($centeredX, 172.5);
}
$pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır





$text2 = $row->sol_taraf_unvan2;
if ($row->unvanlar_kalin_mi === "Kalın") {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('ttimesb', '', $fontSize);
} else {
    $fontSize = 16 - $yuzde916;
    $fontek = 0;
    if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 50) {
        $fontSize = 14 - $yuzde916;
        $fontek = 18;
    }
    $pdf->SetFont('times2', '', $fontSize);
}
$textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
$centeredX = 65 + $fontek - ($textWidth / 2);
if ($row->unvan_konumu === "Üst") {
    $pdf->SetXY($centeredX, 158.5);
} else {
    $pdf->SetXY($centeredX, 165.5);
}
$pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır




}
            }
            
        }

        // PDF çıktısını çıktı olarak ver
        $pdf->Output('sertifikalar.pdf', 'I');
    }


public function generateSmallCertificate()
{
    $userId = auth()->id(); // Auth sınıfı ile giriş yapan kullanıcının ID'sini alıyoruz
    $data = DB::table('ilkyardim')
        ->leftJoin('company_details', 'ilkyardim.user_id', '=', 'company_details.user_id') // company_details ile join
        ->leftJoin('ozel_sertifikas', 'ilkyardim.user_id', '=', 'ozel_sertifikas.user_id') // ozel_sertifikas ile join
        ->where('ilkyardim.user_id', $userId)
        ->select(
            'ilkyardim.*', 
            'company_details.company_name', 
            'company_details.logo_path', 
            'ozel_sertifikas.*' // ozel_sertifikas tablosunun tüm sütunlarını seçiyoruz
        )
        ->get();


    // Yeni bir PDF belgesi oluştur
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(0, 0, 0);  // Sol, üst, sağ kenar boşluklarını sıfırla
    $pdf->SetAutoPageBreak(false); // Sayfa kırılmasını kapat

    foreach ($data as $row) {
        $pdf->AddPage();

        // Arka plan görseli
        if($row->sertifika_sablonu_secimi === "Tam Kaplayan"){
        $imageFile = public_path('images/sertifika.jpg');
        }elseif($row->sertifika_sablonu_secimi === "Ortalanmış"){
          $imageFile = public_path('images/Ortalanmis.jpg');  
        }elseif($row->sertifika_sablonu_secimi === "Küçük"){
            $imageFile = public_path('images/Kucuk.jpg');
        }
        
        $pdf->Image($imageFile, 0, 0, 297, 210); // Resmin boyutları ayarlandı (Yatay A4 boyutları)


$yuzde9 = 0.009;
$yuzde916 =1.44;
$deger10 = 20;
$deger5 = 15;
$yuzde918 =1.62;
$yuzde917 =1.53;
$deger1 = 1;
$deger6 = 6;
$deger22 = 22;
        if($row->logo_konumu === "Sağ"){
        // Logo ekleme
        $logoPath = public_path($row->logo_path); // logo_path veritabanından alınıyor
        if (file_exists($logoPath)) {
            // Logoyu 50x50 mm boyutunda ekliyoruz
            if($row->logo_sekli === "Daire"){
                $pdf->Image($logoPath, 223 + $deger10, 40, 25, 25);
            }elseif($row->logo_sekli === "Kare"){
                $pdf->Image($logoPath, 243- $deger10, 40, 25, 25); 
            }elseif($row->logo_sekli === "Dikdörtgen"){
                $pdf->Image($logoPath, 215- $deger10, 40, 54, 25); 
            }
            
        } else {
            $pdf->SetXY(10, 10);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'Logo Dosyası Bulunamadı', 0, 1, 'L');
        }

        }
        if($row->logo_konumu === "Orta"){
            $logoPath = public_path($row->logo_path);
            if (file_exists($logoPath)) {

                
            if($row->logo_sekli === "Daire"){
                $textWidth = 30; 
                $centeredX = 237 - ($textWidth / 2);
                $pdf->Image($logoPath, $centeredX- $deger10, 40, 25, 25);
            }elseif($row->logo_sekli === "Kare"){
                $textWidth = 30; 
                $centeredX = 237 - ($textWidth / 2);
                $pdf->Image($logoPath, $centeredX- $deger10, 40, 25, 25); 
            }elseif($row->logo_sekli === "Dikdörtgen"){
                $textWidth = 60; 
                $centeredX = 237 - ($textWidth / 2);
                $pdf->Image($logoPath, $centeredX- $deger10, 40, 54, 25); 
            }
            }else{
            $pdf->SetXY(10, 10);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, '', 0, 1, 'L');
            }
            
        }
        if($row->logo_konumu === "Sol"){
            $logoPath = public_path($row->logo_path);
            if (file_exists($logoPath)) {
                $textWidth = 30; 
                $centeredX = 237 - ($textWidth / 2);
            if($row->logo_sekli === "Daire"){
                $pdf->Image($logoPath, 200- $deger10, 40, 25, 25);
            }elseif($row->logo_sekli === "Kare"){
                $pdf->Image($logoPath, 200- $deger10, 40, 25, 25);
            }elseif($row->logo_sekli === "Dikdörtgen"){
                $pdf->Image($logoPath, 190- $deger10, 40, 54, 25); 
            }
                
            }else{
            $pdf->SetXY(10, 10);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, '', 0, 1, 'L');
            }
        }






        // TCPDF'nin eklediği fontu kullan
        $pdf->SetFont('ttimesb', '', 12); // Burada 'times' font adı, yüklenen fontun adı
        

        
        $text = $row->Üst_metin_1;
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $textWidth = $pdf->GetStringWidth($text);
        $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
        $pdf->SetXY($centeredX, 28+$deger5);
        $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');


        
        $text = $row->Üst_metin_2;
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $textWidth = $pdf->GetStringWidth($text);
        $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
        $pdf->SetXY($centeredX, 35+$deger5);
        $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');


        $text = $row->Üst_metin_3;
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $textWidth = $pdf->GetStringWidth($text);
        $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
        $pdf->SetXY($centeredX, 42+$deger5);
        $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');



        $text = "İLKYARDIMCI BELGESİ";
        $pdf->SetFont('ttimesb', '', 16-$yuzde918);
        $textWidth = $pdf->GetStringWidth($text);
        $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
        $pdf->SetXY($centeredX, 85);
        $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');



        $pdf->SetXY(38+$deger10, 67);
        $pdf->SetFont('times2', '', 12-$yuzde916);
        $pdf->Cell(0, 10, "Belge Tipi / No", 0, 1, 'L');
        
        $pdf->SetXY(103, 67);
        $pdf->SetFont('times2', '', 12-$yuzde916);
        $pdf->Cell(0, 10, ":", 0, 1, 'L');

        $pdf->SetXY(38+$deger10, 73);
        $pdf->SetFont('times2', '', 12-$yuzde916);
        $pdf->Cell(0, 10, "Belge Geçerlilik Tarihi", 0, 1, 'L');

        $pdf->SetXY(103, 73);
        $pdf->SetFont('times2', '', 12-$yuzde916);
        $pdf->Cell(0, 10, ":", 0, 1, 'L');
        // Belge No
        $pdf->SetXY(105, 67);
        $pdf->SetFont('ttimesb', '', 13-$yuzde917);
        $pdf->Cell(0, 10, $row->ilkyardim_belge_no, 0, 1, 'L');

        // Belge Geçerlilik Tarihi
        $pdf->SetXY(105, 73);
        $pdf->SetFont('ttimesb', '', 13-$yuzde917);
        $pdf->Cell(0, 10, $this->formatDate($row->ilkyardim_belge_gecerlilik_tarihi), 0, 1, 'L');
        
        // Belge Geçerlilik Tarihi
        $pdf->SetXY(115-$deger5, 95);
        $pdf->SetFont('ttimesb', '', 13-$yuzde917);
        $pdf->Cell(0, 10, "Sayın;", 0, 1, 'L');
        
        $text = $row->katilimci_adi_soyadi;
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $textWidth = $pdf->GetStringWidth($text);
        $centeredX = (297 - $textWidth) / 2;  // 210, A4 sayfasının yatay ölçüsüdür (mm)
        $pdf->SetXY($centeredX, 95);
        $pdf->Cell($textWidth, 10, $text, 0, 1, 'C');

        $pdf->SetXY(32+$deger10, 105); // Yeni bir konum
        $pdf->SetFont('times2', '', 14-$yuzde916);
        $pdf->Cell(0, 10, "İlkyardım yönetmeliği kapsamında", 0, 1, 'L');

        // Eğitim Başlama Tarihi
        $pdf->SetXY(118-$deger1, 105); // Yeni bir konum
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $pdf->Cell(0, 10, $this->formatDate($row->egitim_baslama), 0, 1, 'L');

        // Eğitim Başlama Tarihi
        $pdf->SetXY(139, 105); // Yeni bir konum
        $pdf->SetFont('ttimesb', '', 16);
        $pdf->Cell(0, 10, "-", 0, 1, 'L');


        // Eğitim Bitiş Tarihi
        $pdf->SetXY(142, 105);
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $pdf->Cell(0, 10, $this->formatDate($row->egitim_bitis), 0, 1, 'L');


        $pdf->SetXY(180-$deger5, 105); // Yeni bir konum
        $pdf->SetFont('times2', '', 14-$yuzde916);
        $pdf->Cell(0, 10, "tarihleri arasında", 0, 1, 'L');


        // Company Name (First two words)
        $companyName = $row->company_name;
        $companyNameParts = explode(' ', $companyName); // company_name'ı boşluklardan ayırıyoruz
        $firstTwoWords = implode(' ', array_slice($companyNameParts, 0, 2)); 


        $pdf->SetXY(213-$deger5, 105); // Yeni bir konum
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $pdf->Cell(0, 10, $firstTwoWords, 0, 1, 'L');

        $pdf->SetXY(32+$deger10, 115); // Yeni bir konum
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $pdf->Cell(0, 10, "İLKYARDIM EĞİTİM MERKEZİ", 0, 1, 'L');

        $pdf->SetXY(110+$deger5, 115); // Yeni bir konum
        $pdf->SetFont('times2', '', 14-$yuzde916);
        $pdf->Cell(0, 10, "tarafından düzenlenen", 0, 1, 'L');

        $pdf->SetXY(168-$deger1, 115);
        $pdf->SetFont('ttimesb', '', 14-$yuzde916);
        $pdf->Cell(0, 10, $row->egitim_turu, 0, 1, 'L');

        if ($row->egitim_turu === "İlkyardım Eğitimi"){

            $pdf->SetXY(218-$deger5, 115); // Yeni bir konum
            $pdf->SetFont('times2', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "eğitim programını", 0, 1, 'L');

            $pdf->SetXY(32+$deger10, 125); // Yeni bir konum
            $pdf->SetFont('times2', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "başarı ile bitirerek", 0, 1, 'L');

            $pdf->SetXY(82+$deger6, 125); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "İLKYARDIMCI", 0, 1, 'L');

            $pdf->SetXY(110+$deger5, 125); // Yeni bir konum
            $pdf->SetFont('times2', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "olmaya hak kazanmıştır.", 0, 1, 'L');
        }

        if ($row->egitim_turu === "İlkyardım Eğitimi (Gün.)"){
            $pdf->SetXY(232-$deger5, 115); // Yeni bir konum
            $pdf->SetFont('times2', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "eğitim", 0, 1, 'L');
            
            $pdf->SetXY(32+$deger10, 125); // Yeni bir konum
            $pdf->SetFont('times2', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "programına katılarak", 0, 1, 'L');
            
            $pdf->SetXY(87+$deger6, 125); // Yeni bir konum
            $pdf->SetFont('ttimesb', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "İLKYARDIMCI", 0, 1, 'L');
            
            $pdf->SetXY(113+$deger5, 125); // Yeni bir konum
            $pdf->SetFont('times2', '', 14-$yuzde916);
            $pdf->Cell(0, 10, "belgesi süresi uzatılmıştır.", 0, 1, 'L');
        }




if($row->dogrulama_linki_konumu === "İçerde"){
        $pdf->SetXY(70, 178-$deger5);
        $pdf->SetFont('times2', '', 8);
        $pdf->Cell(0, 10, "Belgeyi Doğrulamak İçin:", 0, 1, 'L');
          // Doğrulama Kodu
        $pdf->SetXY(100, 178-$deger5);
        $pdf->SetFont('ttimesb', '', 8);
        $pdf->Cell(0, 10, $row->dogrulama_kodu, 0, 1, 'L');  
}else{
        $pdf->SetXY(70, 188);
        $pdf->SetFont('times2', '', 8);
        $pdf->Cell(0, 10, "Belgeyi Doğrulamak İçin:", 0, 1, 'L');
        // Doğrulama Kodu
        $pdf->SetXY(100, 188);
        $pdf->SetFont('ttimesb', '', 8);
        $pdf->Cell(0, 10, $row->dogrulama_kodu, 0, 1, 'L');
}

        
        
        
if($row->alt_kac_bolum === "3") {
    if($row->unvan_ve_isimler_konumu === "Üst"){
    
        $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text2 = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
        
        
        
        

        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 138);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 138);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sol_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 138);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    }elseif ($row->unvan_ve_isimler_konumu === "Orta") {
    
    
           $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163.5);
        }else{
            $pdf->SetXY($centeredX, 143);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153.5);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 148);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 159.5);
        }else{
            $pdf->SetXY($centeredX, 143);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $pdf->SetFont('times2', '', 16-$yuzde916);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153.5);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 148);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 159.5);
        }else{
            $pdf->SetXY($centeredX, 143);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153.5);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 148);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }else{
    
    
   $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// ORTA İSİM 
        $text = $row->orta_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        // Yatayda ortalamak için X koordinatını hesapla
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm (A4 yatay), metni ortalamak için X konumunu hesapla
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');
    
        ////
        $text = $row->orta_unvan;
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L');

        $text2 = $row->orta_taraf_unvan2;
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = (297 - $textWidth) / 2; // Sayfa genişliği 297mm, metni ortalamak için X konumunu hesapla
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }
    
    }
            
            
if($row->alt_kac_bolum === "2") {
    if($row->unvan_ve_isimler_konumu === "Üst"){
    
        $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır

        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 140.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır

    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 133);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 140.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 133);
        }else{
            $pdf->SetXY($centeredX, 140.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L');
    
    
    }elseif ($row->unvan_ve_isimler_konumu === "Orta") {
    
    
           $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 159.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153.5);
        }else{
            $pdf->SetXY($centeredX, 159.5);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 153.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
   
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 159.5);
        }else{
            $pdf->SetXY($centeredX, 147);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153.5);
        }else{
            $pdf->SetXY($centeredX, 159.5);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 147);
        }else{
            $pdf->SetXY($centeredX, 153.5);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }else{
    
    
   $text = $row->sag_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{

            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 30) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237 - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sag_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır


        $text2 = $row->sag_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 237  - $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    
    /// SAĞ İSİM
    
        $text = $row->sol_taraf_isim;
        if ($row->isimler_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 163);
        }else{
            $pdf->SetXY($centeredX, 153);
        } // Yatayda X = 60'a ortalamak için X konumunu ayarla
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    
        ////
        $text = $row->sol_taraf_unvan;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 158);
        }else{
            $pdf->SetXY($centeredX, 163);
        }
        $pdf->Cell(0, 10, $text, 0, 1, 'L'); // Metni yazdır
    

        $text2 = $row->sol_taraf_unvan2;
        if ($row->unvanlar_kalin_mi === "Kalın") {
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('ttimesb', '', $fontSize);
        }else{
            $fontSize = 14 - $yuzde916;
            $fontek = 0;
            if ($pdf->GetStringWidth($row->sag_taraf_unvan) > 50) {
                $fontSize = 12 - $yuzde916;
                $fontek = 21;
            }
            $pdf->SetFont('times2', '', $fontSize);
        }
        $textWidth = $pdf->GetStringWidth($text2); // Yazının genişliğini al
        $centeredX = 60  + $fontek - ($textWidth / 2);
        if($row->unvan_konumu === "Üst"){
            $pdf->SetXY($centeredX, 153);
        }else{
            $pdf->SetXY($centeredX, 158);
        }
        $pdf->Cell(0, 10, $text2, 0, 1, 'L'); // Metni yazdır
    
    }
    
    }
            
        }

        // PDF çıktısını çıktı olarak ver
        $pdf->Output('sertifikalar.pdf', 'I');
    }

    // Tarih formatını düzenleyen fonksiyon
    private function formatDate($date)
    {
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateObj ? $dateObj->format('d/m/Y') : $date; // Tarihi 'd/m/Y' formatına çevirir
    }
}
