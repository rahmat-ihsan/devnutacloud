<?php

/**
 * Created by PhpStorm.
 * User: Husnan
 * Date: 10/12/2015
 * Time: 17:22
 */
class MY_Controller extends NutacloudController
{
    function __construct()
    {
        parent::__construct();
    }
}

class Laporan_Controller extends NutacloudController
{
    function __construct()
    {
        parent::__construct();
        $this->load->library('NutaQuery');
        $this->load->library('CurrencyFormatter');
    }

    /**
     * @return array
     */
    protected function get_periode_param()
    {
        $dateStart = $this->input->get('date_start');
        $dateEnd = $this->input->get('date_end');
        return array($dateStart, $dateEnd);
    }

    /**
     * @return mixed
     */
    protected function get_outlet_param()
    {
        $outlet = $this->input->get('outlet');
        if (!isset($outlet)) {
            return $this->getDefaultOutletId();
        }
        return $outlet;
    }

    protected function get_customer_param()
    {
        $customer = $this->input->get('customer');
        if (!isset($customer)) {
            return $this->getDefaultCustomerId();
        }

        return $customer;
    }

    /**
     * @param $dateStart
     * @param $dateEnd
     * @param $outlet
     */
    protected function bind_params_to_NutaQuery($dateStart, $dateEnd, $outlet, $customer = null)
    {
        if ($this->isNotEmpty($dateStart) && $this->isNotEmpty($dateEnd)) {
            $this->nutaquery->setDate($dateStart, $dateEnd);
        }
        if ($this->isNotEmpty($outlet)) {
            $this->nutaquery->SetOutlet($outlet);
        }

        if ($this->isNotEmpty($customer)) {
            $this->nutaquery->SetCustomer($customer);
        }
    }

    /**
     * @param $data
     * @param $availableOutlets
     * @return mixed
     */
    protected function setup_view_params($availableOutlets, $data)
    {
        $data = $this->setupViewOutlets($availableOutlets, $data);
        $data = $this->setupViewPeriode($data);
        $data = $this->setupViewVisibilityMenu($data);
        $data = $this->setupViewVisibilityLaporan($data);
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function setupViewVisibilityLaporan($data)
    {
        $data['isLaporanPembelianVisible'] = $this->IsLaporanPembelianVisible();
        $data['isLaporanStokVisible'] = $this->IsLaporanStokVisible();
        $data['isLaporanPriceVarianVisible'] = $this->IsLaporanVarianHargaVisible();
        $data['isLaporanOpsiMakanVisible'] = $this->IsLaporanOpsiMakanVisible();
        return $data;
    }

    /**
     * @param $availableOutlets
     * @param $data
     * @return mixed
     */
    protected function setupViewOutlets($availableOutlets, $data)
    {
        $data['outlets'] = $availableOutlets;
        $data['selected_outlet'] = $this->nutaquery->getOutlet();
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function setupViewPeriode($data)
    {
        $data['date_start'] = $this->nutaquery->getDateStart();
        $data['date_end'] = $this->nutaquery->getDateEnd();
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function setupViewVisibilityMenu($data)
    {
        $data['visibilityMenu'] = $this->visibilityMenu;
        return $data;
    }

    protected function setupViewListCustomer($customers, $data)
    {
        $data['customers'] = $customers;
        $data['selected_customer'] = $this->nutaquery->getCustomer();
        return $data;
    }
}

class NutacloudController extends CI_Controller
{
    var $visibilityMenu = array(
        'Dashboard' => true,
        'LaporanPenjualan' => true,
        'LaporanPenjualanVarianperShift' => true,
        'LaporanPenjualanPilihanekstraperShift' => true,
        'LaporanRekapPenjualan' => true,
        'LaporanRekapPenjualanPerKategori' => true,
        'LaporanRekapPembayaran' => true,
        'LaporanPembelian' => true,
        'LaporanRekapPembelian' => true,
        'LaporanSaldoKas/Rekening' => true,
        'LaporanStok' => true,
        'LaporanKartuStok' => true,
        'LaporanRekapMutasiStok' => true,
        'LaporanLaba' => true,
        'LaporanLabaPerKategori' => true,
        'HapusData' => true,
        'LaporanPenjualanVarianHarga' => true,
        'LaporanPengeluaran' => true,
        'LaporanAkuntansi' => true,
        'LaporanPenjualanPerOutletPerHari' => true
    );
    protected $default_outlet_id;
    protected $default_customer_id;

    function __construct()
    {
        parent::__construct();
        $this->load->helper('nuta_helper');
        ifNotAuthenticatedRedirectToLogin();
        $this->load->database();
        $this->load->library('session');

        $namaperusahaan = getLoggedInNamaPerusahaan();
        if (isset($namaperusahaan) && $namaperusahaan != 'Individual') {
            $username = getLoggedInUsername();
            $idperusahaan = getLoggedInUserID();
            $this->load->model('Useraksescloud');
            $akses = $this->Useraksescloud->getAkses($username, $idperusahaan);
            $this->visibilityMenu['Dashboard'] = ($akses->Dashboard == 1);
            $this->visibilityMenu['LaporanPenjualan'] = ($akses->LaporanPenjualan == 1);
            $this->visibilityMenu['LaporanRekapPenjualan'] = ($akses->LaporanRekapPenjualan == 1);
            $this->visibilityMenu['LaporanRekapPenjualanPerKategori'] = ($akses->LaporanRekapPenjualanPerKategori == 1);
            $this->visibilityMenu['LaporanRekapPembayaran'] = ($akses->LaporanRekapPembayaran == 1);
            $this->visibilityMenu['LaporanPembelian'] = ($akses->LaporanPembelian == 1);
            $this->visibilityMenu['LaporanRekapPembelian'] = ($akses->LaporanRekapPembelian == 1);
            $this->visibilityMenu['LaporanSaldoKasRekening'] = ($akses->LaporanSaldoKasRekening == 1);
            $this->visibilityMenu['LaporanStok'] = ($akses->LaporanStok == 1);
            $this->visibilityMenu['LaporanKartuStok'] = ($akses->LaporanKartuStok == 1);
            $this->visibilityMenu['LaporanRekapMutasiStok'] = ($akses->LaporanRekapMutasiStok == 1);
            $this->visibilityMenu['LaporanLaba'] = ($akses->LaporanLaba == 1);
            $this->visibilityMenu['LaporanLabaPerKategori'] = ($akses->LaporanLaba == 1);
            $this->visibilityMenu['LaporanLabaPerShift'] = ($akses->LaporanLaba == 1);
            $this->visibilityMenu['LaporanRincianLabaPerShift'] = ($akses->LaporanLaba == 1);
            $this->visibilityMenu['HapusData'] = ($akses->HapusData == 1);
            $this->visibilityMenu['LaporanPengeluaran'] = ($akses->LaporanPengeluaran == 1);
            $this->visibilityMenu['LaporanPenjualanVarianperShift'] = ($akses->LaporanRekapPenjualan == 1 && $this->IsLaporanVarianHargaVisible());
            $this->visibilityMenu['LaporanPenjualanPilihanekstraperShift'] = ($akses->LaporanRekapPenjualan == 1 && $this->IsLaporanVarianHargaVisible());
            // New November 2019
            $this->visibilityMenu['LaporanPenjualanPerJam'] = ($akses->LaporanPenjualanPerJam == 1);
            $this->visibilityMenu['LaporanPenjualanPerKasir'] = ($akses->LaporanPenjualanPerKasir == 1);
            $this->visibilityMenu['LaporanRekapPenjualanPerKategori'] = ($akses->LaporanRekapPenjualanPerKategori == 1);
            $this->visibilityMenu['LaporanRataRataBelanjaPelanggan'] = ($akses->LaporanRataRataBelanjaPelanggan == 1);
            $this->visibilityMenu['LaporanDiskon'] = ($akses->LaporanDiskon == 1);
            $this->visibilityMenu['LaporanPajak'] = ($akses->LaporanPajak == 1);
            $this->visibilityMenu['LaporanPenjualanVarian'] = ($akses->LaporanPenjualanVarian == 1);
            $this->visibilityMenu['LaporanPenjualanPilihanEkstra'] = ($akses->LaporanPenjualanPilihanEkstra == 1);
            $this->visibilityMenu['LaporanPembulatan'] = ($akses->LaporanPembulatan == 1);
            $this->visibilityMenu['LaporanPenjualanVoid'] = ($akses->LaporanPenjualanVoid == 1);
            $this->visibilityMenu['LaporanPesananBelumLunas'] = ($akses->LaporanPesananBelumLunas == 1);
            $this->visibilityMenu['LaporanPenjualanPerTipe'] = ($akses->LaporanPenjualanPerTipe == 1);
            $this->visibilityMenu['LaporanPenjualanPerJamItem'] = ($akses->LaporanPenjualanPerJamItem == 1);
            $this->visibilityMenu['LaporanRiwayatBelanjaPelanggan'] = ($akses->LaporanRiwayatBelanjaPelanggan == 1);
            $this->visibilityMenu['LaporanPesananBatal'] = ($akses->LaporanPesananBatal == 1);
            $this->visibilityMenu['LaporanPenjualanPerKategoriSemuaItem'] = ($akses->LaporanPenjualanPerKategoriSemuaItem == 1);
            $this->visibilityMenu['LaporanPenjualanPerShift'] = ($akses->LaporanPenjualanPerShift == 1);
            $this->visibilityMenu['LaporanRekapPenjualanPerShift'] = ($akses->LaporanRekapPenjualanPerShift == 1);
            $this->visibilityMenu['LaporanRekapShift'] = ($akses->LaporanRekapShift == 1);
            $this->visibilityMenu['LaporanPenjualanPerKasirShift'] = ($akses->LaporanPenjualanPerKasirShift == 1);
            $this->visibilityMenu['LaporanPenjualanVarianPerShift'] = ($akses->LaporanPenjualanVarianPerShift == 1);
            $this->visibilityMenu['LaporanPenjualanKategoriPerShift'] = ($akses->LaporanPenjualanKategoriPerShift == 1);
            $this->visibilityMenu['LaporanPenjualanPilihanEkstraPerShift'] = ($akses->LaporanPenjualanPilihanEkstraPerShift == 1);
            $this->visibilityMenu['LaporanTipePenjualanPerShift'] = ($akses->LaporanTipePenjualanPerShift == 1);

            $this->visibilityMenu['LaporanMutasiKasRekening'] = ($akses->LaporanMutasiKasRekening == 1);
            $this->visibilityMenu['LaporanPengeluaranPerDibayarKe'] = ($akses->LaporanPengeluaranPerDibayarKe == 1);


            $this->visibilityMenu['ItemView'] = ($akses->ItemAdd == 1 || $akses->ItemEdit == 1 || $akses->ItemDelete == 1);
            $this->visibilityMenu['ItemAdd'] = ($akses->ItemAdd == 1);
            $this->visibilityMenu['ItemEdit'] = ($akses->ItemEdit == 1);
            $this->visibilityMenu['ItemDelete'] = ($akses->ItemDelete == 1);

            $this->visibilityMenu['CustomerView'] = ($akses->CustomerAdd == 1 || $akses->CustomerEdit == 1 || $akses->CustomerDelete == 1);
            $this->visibilityMenu['CustomerAdd'] = ($akses->CustomerAdd == 1);
            $this->visibilityMenu['CustomerEdit'] = ($akses->CustomerEdit == 1);
            $this->visibilityMenu['CustomerDelete'] = ($akses->CustomerDelete == 1);

            $this->visibilityMenu['SupplierView'] = ($akses->SupplierAdd == 1 || $akses->SupplierEdit == 1 || $akses->SupplierDelete == 1);
            $this->visibilityMenu['SupplierAdd'] = ($akses->SupplierAdd == 1);
            $this->visibilityMenu['SupplierEdit'] = ($akses->SupplierEdit == 1);
            $this->visibilityMenu['SupplierDelete'] = ($akses->SupplierDelete == 1);

            $this->visibilityMenu['StockView'] = ($akses->StockAdd == 1 || $akses->StockEdit == 1 || $akses->StockDelete == 1);
            $this->visibilityMenu['StockAdd'] = ($akses->StockAdd == 1);
            $this->visibilityMenu['StockEdit'] = ($akses->StockEdit == 1);
            $this->visibilityMenu['StockDelete'] = ($akses->StockDelete == 1);

            $this->visibilityMenu['MoneyView'] = ($akses->MoneyAdd == 1 || $akses->MoneyEdit == 1 || $akses->MoneyDelete == 1);
            $this->visibilityMenu['MoneyAdd'] = ($akses->MoneyAdd == 1);
            $this->visibilityMenu['MoneyEdit'] = ($akses->MoneyEdit == 1);
            $this->visibilityMenu['MoneyDelete'] = ($akses->MoneyDelete == 1);

            $this->visibilityMenu['PurchaseView'] = ($akses->PurchaseAdd == 1 || $akses->PurchaseEdit == 1 || $akses->PurchaseDelete == 1);
            $this->visibilityMenu['PurchaseAdd'] = ($akses->PurchaseAdd == 1);
            $this->visibilityMenu['PurchaseEdit'] = ($akses->PurchaseEdit == 1);
            $this->visibilityMenu['PurchaseDelete'] = ($akses->PurchaseDelete == 1);

            $this->visibilityMenu['DataRekeningView'] = ($akses->DataRekeningAdd == 1 || $akses->DataRekeningEdit == 1 || $akses->DataRekeningDelete == 1);
            $this->visibilityMenu['DataRekeningAdd'] = ($akses->DataRekeningAdd == 1);
            $this->visibilityMenu['DataRekeningEdit'] = ($akses->DataRekeningEdit == 1);
            $this->visibilityMenu['DataRekeningDelete'] = ($akses->DataRekeningDelete == 1);

            $this->visibilityMenu['AkunView'] = ($akses->AkunAdd == 1 || $akses->AkunEdit == 1 || $akses->AkunDelete == 1);
            $this->visibilityMenu['AkunAdd'] = ($akses->AkunAdd == 1);
            $this->visibilityMenu['AkunEdit'] = ($akses->AkunEdit == 1);
            $this->visibilityMenu['AkunDelete'] = ($akses->AkunDelete == 1);

            $this->visibilityMenu['JournalView'] = ($akses->JournalAdd == 1 || $akses->JournalEdit == 1 || $akses->JournalDelete == 1);
            $this->visibilityMenu['JournalAdd'] = ($akses->JournalAdd == 1);
            $this->visibilityMenu['JournalEdit'] = ($akses->JournalEdit == 1);
            $this->visibilityMenu['JournalDelete'] = ($akses->JournalDelete == 1);

            $this->visibilityMenu['CalculateJournalAuto'] = ($akses->CalculateJournalAuto == 1);

            // Tambahan November 2019
            $this->visibilityMenu['OutletView'] = ($akses->OutletNew == 1 || $akses->OutletEdit == 1 || $akses->OutletDelete == 1);
            $this->visibilityMenu['OutletNew'] = ($akses->OutletNew == 1);
            $this->visibilityMenu['OutletEdit'] = ($akses->OutletEdit == 1);
            $this->visibilityMenu['OutletDelete'] = ($akses->OutletDelete == 1);

            $this->visibilityMenu['PromoView'] = ($akses->PromoNew == 1 || $akses->PromoEdit == 1 || $akses->PromoDelete == 1);
            $this->visibilityMenu['PromoNew'] = ($akses->PromoNew == 1);
            $this->visibilityMenu['PromoEdit'] = ($akses->PromoEdit == 1);
            $this->visibilityMenu['PromoDelete'] = ($akses->PromoDelete == 1);

            $this->visibilityMenu['UserView'] = ($akses->UserNew == 1 || $akses->UserEdit == 1 || $akses->UserDelete == 1);
            $this->visibilityMenu['UserNew'] = ($akses->UserNew == 1);
            $this->visibilityMenu['UserEdit'] = ($akses->UserEdit == 1);
            $this->visibilityMenu['UserDelete'] = ($akses->UserDelete == 1);

            $this->visibilityMenu['IncomingStockView'] = ($akses->IncomingStockNew == 1 || $akses->IncomingStockEdit == 1 || $akses->IncomingStockDelete == 1);
            $this->visibilityMenu['IncomingStockNew'] = ($akses->IncomingStockNew == 1);
            $this->visibilityMenu['IncomingStockEdit'] = ($akses->IncomingStockEdit == 1);
            $this->visibilityMenu['IncomingStockDelete'] = ($akses->IncomingStockDelete == 1);

            $this->visibilityMenu['OutgoingStockView'] = ($akses->OutgoingStockNew == 1 || $akses->OutgoingStockEdit == 1 || $akses->OutgoingStockDelete == 1);
            $this->visibilityMenu['OutgoingStockNew'] = ($akses->OutgoingStockNew == 1);
            $this->visibilityMenu['OutgoingStockEdit'] = ($akses->OutgoingStockEdit == 1);
            $this->visibilityMenu['OutgoingStockDelete'] = ($akses->OutgoingStockDelete == 1);

            $this->visibilityMenu['TransferStockView'] = ($akses->TransferStockNew == 1 || $akses->TransferStockEdit == 1 || $akses->TransferStockDelete == 1);
            $this->visibilityMenu['TransferStockNew'] = ($akses->TransferStockNew == 1);
            $this->visibilityMenu['TransferStockEdit'] = ($akses->TransferStockEdit == 1);
            $this->visibilityMenu['TransferStockDelete'] = ($akses->TransferStockDelete == 1);

            $this->visibilityMenu['Aktivasi'] = ($akses->Aktivasi == 1);

            $this->visibilityMenu['CashBankOutView'] = ($akses->CashBankOutNew == 1 || $akses->CashBankOutEdit == 1 || $akses->CashBankOutDelete == 1);
            $this->visibilityMenu['CashBankOutNew'] = ($akses->CashBankOutNew == 1);
            $this->visibilityMenu['CashBankOutEdit'] = ($akses->CashBankOutEdit == 1);
            $this->visibilityMenu['CashBankOutDelete'] = ($akses->CashBankOutDelete == 1);
        }
    }

    protected function IsLaporanStokVisible()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) { // Individual
            $query = $this->db->get_where('options', array('deviceid' => getLoggedInRegisterWithDeviceID(), 'StockModule' => 'true'));
            echo $this->db->last_query(); exit();
            $numrows = $query->num_rows();
            return $numrows == 1;
        } else {
            // Perusahaan
            $this->load->model('Perusahaanmodel');
            $modulperusahaan = $this->Perusahaanmodel->getModulPerusahaan(getLoggedInUserID());
            return $modulperusahaan['IsUseStockModule'] == 1;
        }
    }

    protected function IsStokBahanVisible()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) { // Individual
            $query = $this->db->get_where('options', array('deviceid' => getLoggedInRegisterWithDeviceID(), 'StockModule' => 'true'));
            $numrows = $query->num_rows();
            return $numrows == 1;
        } else {
            // Perusahaan
            $this->load->model('Perusahaanmodel');
            $modulperusahaan = $this->Perusahaanmodel->getModulPerusahaan(getLoggedInUserID());
            return $modulperusahaan['IsUseIngredientsModule'] == 1;
        }
    }

    protected function IsLaporanPembelianVisible()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) { // Individual
            $query = $this->db->get_where('options', array('deviceid' => getLoggedInRegisterWithDeviceID(), 'PurchaseModule' => 'true'));
            $numrows = $query->num_rows();
            return $numrows == 1;
        } else {
            // Perusahaan
            $this->load->model('Perusahaanmodel');
            $modulperusahaan = $this->Perusahaanmodel->getModulPerusahaan(getLoggedInUserID());
            return $modulperusahaan['IsUsePurchaseModule'] == 1;
        }
    }

    protected function IsLaporanVarianHargaVisible()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) { // Individual
            $query = $this->db->get_where('options', array('deviceid' => getLoggedInRegisterWithDeviceID(), 'PriceVariation' => '1'));
            $numrows = $query->num_rows();
            return $numrows == 1;
        } else {
            // Perusahaan
            $this->load->model('Perusahaanmodel');
            $modulperusahaan = $this->Perusahaanmodel->getModulPerusahaan(getLoggedInUserID());
            return $modulperusahaan['IsUseVarianAndPrice'] == 1;
        }
    }

    protected function IsLaporanOpsiMakanVisible()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) { // Individual
            $query = $this->db->get_where('options', array('deviceid' => getLoggedInRegisterWithDeviceID(), 'PriceVariation' => '1'));
            $numrows = $query->num_rows();
            return $numrows == 1;
        } else {
            // Perusahaan
            $query = $this->db->get_where('options', array('PerusahaanNo' => getPerusahaanNo(), 'CAST(EditedVersionCode AS SIGNED)>=' => '150'));
            $numrows = $query->num_rows();
            return $numrows >= 1;
        }
    }

    protected function IsPromoVisible()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) {
            //individual
            return false;
        } else {
            // Perusahaan
            $query = $this->db->get_where('options', array('PerusahaanNo' => getPerusahaanNo(), 'CAST(EditedVersionCode AS SIGNED)>=' => '163'));
            $numrows = $query->num_rows();
            return $numrows >= 1;
        }
    }

    protected function IsDiningTableVisible()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) { // Individual
            $query = $this->db->get_where('options', array('deviceid' => getLoggedInRegisterWithDeviceID(), 'DiningTable' => '1'));
            $numrows = $query->num_rows();
            return $numrows == 1;
        } else {
            // Perusahaan
            $this->load->model('Perusahaanmodel');
            $modulperusahaan = $this->Perusahaanmodel->getModulPerusahaan(getLoggedInUserID());
            return $modulperusahaan['IsDiningTable'] == 1;
        }
    }

    protected function IsUsePushNotification()
    {
        if (getLoggedInRegisterWithDeviceID() == getLoggedInUserID()) { // Individual
            $query = $this->db->get_where('options', array('deviceid' => getLoggedInRegisterWithDeviceID(), 'DiningTable' => '1'));
            $numrows = $query->num_rows();
            return $numrows == 1;
        } else {
            // Perusahaan
            $this->load->model('Perusahaanmodel');
            $modulperusahaan = $this->Perusahaanmodel->getModulPerusahaan(getLoggedInUserID());
            return $modulperusahaan['IsUsePushNotification'] == 1;
        }
    }

    public function FilterOutletWithItem($arrayofoutlet, $namaitem)
    {
        $retval = array();
        foreach ($arrayofoutlet as $k => $outlet) {
            $this->db->where(array('DeviceID' => $k, 'ItemName' => $namaitem));
            $query = $this->db->get('masteritem');
            $count = $query->num_rows();
            if ($count == 1) {
                $item = $query->result();
                $retval[$k] = $outlet; //. '#$%^' . $item[0]->ItemID;;
            }
        }
        return $retval;
    }

    public function getOutletByVersion($version, $type){
        $sql = "SELECT a.* FROM outlet a
            INNER JOIN options b ON a.PerusahaanNo = b.PerusahaanNo AND a.OutletID = b.DeviceID
            WHERE a.PerusahaanID = ? ";

        if($type == ">"){
            $sql .= " AND (CAST(b.CreatedVersionCode AS UNSIGNED) > ? OR CAST(b.EditedVersionCode AS UNSIGNED) > ? ) ";
        } elseif($type == "<") {
            $sql .= " AND (CAST(b.CreatedVersionCode AS UNSIGNED) < ? OR CAST(b.EditedVersionCode AS UNSIGNED) < ? ) ";
        }elseif($type == ">=") {
            $sql .= " AND (CAST(b.CreatedVersionCode AS UNSIGNED) >= ? OR CAST(b.EditedVersionCode AS UNSIGNED) >= ? ) ";
        }elseif($type == "<="){
            $sql .= " AND (CAST(b.CreatedVersionCode AS UNSIGNED) <= ? OR CAST(b.EditedVersionCode AS UNSIGNED) <= ? ) ";
        }

        $query = $this->db->query($sql, array(getLoggedInUserID(), $version, $version));
        $result =  $query->result();
        $retval = array();
        $this->load->model('Userperusahaan');
        if (getLoggedInNamaPerusahaan() != "Individual") {
            $isOwner = $this->Userperusahaan->isUserOwner(getLoggedInUserID(), getLoggedInUsername());
        } else {
            $isOwner = true;
        }
        if ($isOwner) {
            foreach ($result as $row) {
                $retval[$row->OutletID] = $row->NamaOutlet . '#$%^' . $row->AlamatOutlet;
            }
        } else {
            $this->load->model('Userperusahaancabang');
            $availableCabangs = $this->Userperusahaancabang->getListCabang(getLoggedInUsername(), getLoggedInUserID());
            foreach ($result as $row) {
                foreach ($availableCabangs as $cabang) {
                    if ($cabang->OutletID == $row->OutletID) {
                        $retval[$row->OutletID] = $row->NamaOutlet . '#$%^' . $row->AlamatOutlet;
                    }
                }
            }
        }
        return $retval;
    }

    public function GetOutletTanpaSemua()
    {
        $id = $this->db->escape(getLoggedInUserID());
        $where = "perusahaanid = " . $id;

        $this->db->where($where);
        $query = $this->db->get('outlet');
        $result = $query->result();
        $retval = array();
        $this->load->model('Userperusahaan');
        if (getLoggedInNamaPerusahaan() != "Individual") {
            $isOwner = $this->Userperusahaan->isUserOwner(getLoggedInUserID(), getLoggedInUsername());
        } else {
            $isOwner = true;
        }
        if ($isOwner) {
            foreach ($result as $row) {
                $retval[$row->OutletID] = $row->NamaOutlet . '#$%^' . $row->AlamatOutlet;
            }
        } else {
            $this->load->model('Userperusahaancabang');
            $availableCabangs = $this->Userperusahaancabang->getListCabang(getLoggedInUsername(), getLoggedInUserID());
            foreach ($result as $row) {
                foreach ($availableCabangs as $cabang) {
                    if ($cabang->OutletID == $row->OutletID) {
                        $retval[$row->OutletID] = $row->NamaOutlet . '#$%^' . $row->AlamatOutlet;
                    }
                }
            }
        }
        return $retval;
    }

    public function setDefaultOutletId($availableOutlets)
    {
        $keyOutlet = array_keys($availableOutlets);
        if (count($availableOutlets) > 0) {
            $this->default_outlet_id = $keyOutlet[0];
        }
    }

    public function getDefaultOutletId()
    {
        return $this->default_outlet_id;
    }

    public function GetCustomers($idoutlet)
    {
        $this->load->model('Mastercustomer');
        $customers = $this->Mastercustomer->findAll($idoutlet);
        $this->setDefaultCustomerId($customers);
        return $customers;
    }

    public function getDefaultCustomerId()
    {
        return $this->default_customer_id;
    }

    public function setDefaultCustomerId($customers)
    {
        if (count($customers) > 0) {
            $this->default_customer_id = $customers[0]->CustomerID . '.' . $customers[0]->DeviceNo;
        }
    }
}
