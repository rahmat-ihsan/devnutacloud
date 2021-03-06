<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Husnan
 * Date: 15/02/2016
 * Time: 11:51
 */
class Masteritem extends CI_Model
{
    var $_tableName = "masteritem";
    protected $_dbMaster;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Firebasemodel');
    }

    protected function initDbMaster()
    {
        $this->_dbMaster = $this->load->database('master', true);
    }

    public function createNewItem($namaitem, $idkategori, $kategoridevno, $namasatuan, $hargajual, $hargabeli, $isProduct, $punyabahan, $idoutlet, $perusahaanNo)
    {
        $this->initDbMaster();
        if ($isProduct == 'false' || ($isProduct == 'true' && $punyabahan == 'false'))
            $usestock = 'true';
        else
            $usestock = 'false';
        //$usestock = ($punyabahan == 'true' ? 'false' : 'true');
        $this->_dbMaster->where(array('PerusahaanNo' => $perusahaanNo, 'DeviceID' => $idoutlet, 'lower(ItemName)' => strtolower($namaitem)));
        $query = $this->_dbMaster->get($this->_tableName);
        $count = $query->num_rows();
        if ($count > 0) {
            $result = $query->result();
            return 'Item ini sudah ada';
        } else {

            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            $cloudDevno = 0;
            if ($options->CreatedVersionCode < 103 && $options->EditedVersionCode < 103) {
                $cloudDevno = 1;
            }

            $itemid = $this->getMaxItemID($idoutlet, $perusahaanNo);
            $rownumber = $this->getMaxRowNumber($idoutlet, $perusahaanNo, $idkategori, $kategoridevno);
            $attrib = array(
                'ItemID' => $itemid,
                'DeviceNo' => $cloudDevno,
                'ItemName' => $namaitem,
                'CategoryID' => $idkategori,
                'CategoryDeviceNo' => $kategoridevno,
                'Unit' => $namasatuan,
                'SellPrice' => $hargajual,
                'PurchasePrice' => $hargabeli,
                'IsProduct' => $isProduct,
                'IsProductHasIngredients' => $punyabahan,
                'Stock' => $usestock,
                'HasBeenDownloaded' => 0,
                'IsDetailsSaved' => 'true',
                'RowNumber' => $rownumber,
                'Barcode' => '',
                'BeginningStock' => 0,
                'BeginningCOGS' => 0,
                'ImageLink' => '',
                'TaxPercent' => 0,
                'SellPriceIncludeTax' => 'false',
                'SplitPosition' => 1,
                'OnlineImagePath' => '',
                'DeviceID' => $idoutlet, 'Varian' => 'Nuta', 'PerusahaanNo' => $perusahaanNo
            );

            $this->_dbMaster->insert($this->_tableName, $attrib);

            // push data to firebase
            $query_datainserted = $this->_dbMaster->get_where($this->_tableName, array(
                'ItemID' => $itemid,
                'DeviceID' => $idoutlet,
                'DeviceNo' => $cloudDevno,
                'PerusahaanNo' => $perusahaanNo
            ));
            $last_insert_data = array(
                "table" => $this->_tableName,
                "column" => $query_datainserted->row_array()
            );

            // $this->load->model('Options');
            // $options = $this->Options->get_by_devid($idoutlet);
            // if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
            $this->Firebasemodel->push_firebase(
                $idoutlet,
                $last_insert_data,
                $itemid,
                $cloudDevno,
                $perusahaanNo,
                0
            );
            // }

            return $itemid . "." . $cloudDevno;
        }
    }

    public function getMasterItemByOutlet($idOutlet)
    {
        $query = $this->db->query("SELECT ItemID, DeviceNo, ItemName, PurchasePrice, Unit, CategoryID FROM " . $this->_tableName . "
 WHERE deviceid=" . $idOutlet . " AND PerusahaanNo=" . getPerusahaanNo() .
            " AND (IsProduct='false' OR (IsProduct='true' AND IsProductHasIngredients='false'))");

        return $query->result();
        //        $this->db->select('ItemID, ItemName, PurchasePrice, Unit');
        //        $this->db->where('DeviceID', $idOutlet);
        //        $query = $this->db->get($this->_tableName);
        //
        //        return $query->result();
    }

    public function getMasterItemStokByOutlet($idOutlet)
    {
        $query = $this->db->query("SELECT ItemID, DeviceNo, ItemName, PurchasePrice, Unit FROM " . $this->_tableName . "
 WHERE deviceid=" . $idOutlet . " AND PerusahaanNo=" . getPerusahaanNo() .
            " AND (IsProduct='false' OR (IsProduct='true' AND IsProductHasIngredients='false'))");

        return $query->result();
    }

    public function getByName($namaItem, $idoutlet)
    {
        $query = $this->db->get_where($this->_tableName, array('ItemName' => $namaItem, 'DeviceID' => $idoutlet));
        $result = $query->result();
        if (count($result) > 0) {
            return $result[0];
        } else {
            return null;
        }
    }

    public function getByID($id, $idoutlet)
    {
        $realitemid = explode(".", $id)[0];
        $devno = explode(".", $id)[1];

        $query = $this->db->get_where($this->_tableName, array(
            'ItemID' => $realitemid,
            'DeviceNo' => $devno, 'DeviceID' => $idoutlet
        ));
        $result = $query->result();
        if (count($result) > 0) {
            return $result[0];
        } else {
            return null;
        }
    }

    public function setNonAktif($idperusahaan, $idoutlet)
    {
        $this->initDbMaster();
        $this->_dbMaster->where(array('PerusahaanID' => $idperusahaan, 'OutletID' => $idoutlet));
        $this->_dbMaster->update($this->_tableName, array('DeviceIDAktif' => ''));
    }

    public function createItemBahan($namaitem, $nama_satuan, $idoutlet, $perusahaanNo, $hargaBeli)
    {
        $this->initDbMaster();
        $itemid = $this->getMaxItemID($idoutlet, $perusahaanNo);
        $rowNumber = $this->getMaxRowNumber($idoutlet, $perusahaanNo, 0, 1);
        $this->_dbMaster->where(array('DeviceID' => $idoutlet, 'ItemName' => $namaitem));
        $query = $this->_dbMaster->get($this->_tableName);
        $count = $query->num_rows();
        if ($count > 0) {
            $result = $query->result();
            return $result[0]->ItemID . "." . $result[0]->DeviceNo;
        } else {

            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            $cloudDevno = 0;
            if ($options->CreatedVersionCode < 103 && $options->EditedVersionCode < 103) {
                $cloudDevno = 1;
            }

            $attrib = array(
                'ItemID' => $itemid,
                'DeviceNo' => $cloudDevno,
                'ItemName' => $namaitem, 'CategoryID' => 0, 'CategoryDeviceNo' => 1, 'Unit' => $nama_satuan, 'Unit' => $nama_satuan,
                'SellPrice' => 0, 'PurchasePrice' => 0, 'IsProduct' => 'false', 'IsProductHasIngredients' => 'false',
                'DeviceID' => $idoutlet, 'Varian' => 'Nuta', 'HasBeenDownloaded' => 0, 'Stock' => 'true', 'PerusahaanNo' => $perusahaanNo,
                'RowNumber' => $rowNumber,
                'Barcode' => '',
                'BeginningStock' => 0,
                'BeginningCOGS' => 0,
                'ImageLink' => '',
                'TaxPercent' => 0,
                'SellPriceIncludeTax' => 'false',
                'SplitPosition' => 1,
                'OnlineImagePath' => '',
                'IsDetailsSaved' => 'true',
                'PurchasePrice' => $hargaBeli
            );

            $this->_dbMaster->insert($this->_tableName, $attrib);
            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            // push data to firebase
            if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
                $query_datainserted = $this->_dbMaster->get_where($this->_tableName, array(
                    'PerusahaanNo' => $perusahaanNo,
                    'DeviceID' => $idoutlet,
                    'ItemID' => $itemid,
                    'DeviceNo' => $cloudDevno
                ));
                $last_insert_data = array(
                    "table" => $this->_tableName,
                    "column" => $query_datainserted->row_array()
                );
                $this->Firebasemodel->push_firebase(
                    $idoutlet,
                    $last_insert_data,
                    $itemid,
                    $cloudDevno,
                    $perusahaanNo,
                    0
                );
            } else {
                $this->Firebasemodel->push_firebase(
                    $idoutlet,
                    array(
                        'table' => 'pleaseUpdateMasterItem',
                        'column' => array('ItemID' => $itemid, 'DeviceNo' => $cloudDevno)
                    ),
                    $itemid,
                    $cloudDevno,
                    $perusahaanNo,
                    0
                );
            }

            return $itemid . "." . $cloudDevno;
        }
    }

    public function createNewLinkItemBahan($idItem, $idBahan, $detailNumber, $qty, $idoutlet, $perusahaanNo)
    {
        $realitemid = explode(".", $idItem)[0];
        $devno = explode(".", $idItem)[1];
        $realidbahan = explode(".", $idBahan)[0];
        $devnobahan = explode(".", $idBahan)[1];
        $this->initDbMaster();
        $sql_generate_id = "
        SELECT
          COALESCE (MAX(DetailID),0) +1 as ID
        FROM
        (SELECT
            DetailID
        FROM
            masteritemdetailingredients
        WHERE
            DeviceID = " . $this->db->escape($idoutlet) . " UNION ALL SELECT
            DetailID
        FROM
            masteritemdetailingredientsdelete
        WHERE
            DeviceID = " . $this->db->escape($idoutlet) . ") a
        ";
        $queryid = $this->db->query($sql_generate_id);
        $resultid = $queryid->result();
        $detailid = $resultid[0]->ID;

        $this->load->model('Options');
        $options = $this->Options->get_by_devid($idoutlet);
        $cloudDevno = 0;
        if ($options->CreatedVersionCode < 103 && $options->EditedVersionCode < 103) {
            $cloudDevno = 1;
        }

        $attrib_data_ingredient = array(
            'DetailID' => $detailid,
            'DeviceNo' => $cloudDevno,
            'ItemID' => $realitemid,
            'ItemDeviceNo' => $devno,
            'DetailNumber' => $detailNumber,
            'IngredientsID' => $realidbahan,
            'IngredientsDeviceNo' => $devnobahan,
            'QtyNeed' => $qty,
            'DeviceID' => $idoutlet, 'Varian' => 'Nuta',
            'PerusahaanNo' => $perusahaanNo,
            'HasBeenDownloaded' => 0
        );

        $insert_masteritemdetailingredients = $this->_dbMaster->insert(
            'masteritemdetailingredients',
            $attrib_data_ingredient
        );

        if ($insert_masteritemdetailingredients) {
            $query_datainserted = $this->_dbMaster->get_where('masteritemdetailingredients', array(
                'PerusahaanNo' => $perusahaanNo,
                'DeviceID' => $idoutlet,
                'DetailID' => $detailid,
                'DeviceNo' => $cloudDevno
            ));
            $last_insert_data = array(
                "table" => 'masteritemdetailingredients',
                "column" => $query_datainserted->row_array()
            );
            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
                $this->Firebasemodel->push_firebase(
                    $idoutlet,
                    $last_insert_data,
                    $detailid,
                    $cloudDevno,
                    $perusahaanNo,
                    0
                );
            }
        }

        return $detailid . "." . $cloudDevno;
    }

    public function deleteItem($namaItem, $arrayOfidOutlet)
    {
        $this->initDbMaster();
        $perusahaanNo = getPerusahaanNo();
        foreach ($arrayOfidOutlet as $idoutlet) {
            $query_is_modifier = $this->_dbMaster->get_where('mastermodifierdetail', array('DeviceID' => $idoutlet, 'ChoiceName' => $namaItem));
            $is_it_modifier = $query_is_modifier->num_rows() > 0;
            if ($is_it_modifier) {
                return "Item " . $namaItem . " tidak bisa dihapus karena  bagian dari pilihan ekstra.";
            }

            $query_deleting_item = $this->_dbMaster->get_where(
                'masteritem',
                array('DeviceID' => $idoutlet, 'ItemName' => $namaItem)
            );
            $deleting_items = $query_deleting_item->result_array();

            /*Cek apakah digunakan oleh produk lain*/
            foreach ($deleting_items as $deleting_item) {
                $itemID = $deleting_item['ItemID'];
                $query_is_bahan_used = $this->_dbMaster->get_where(
                    'masteritemdetailingredients',
                    array(
                        'DeviceID' => $idoutlet, 'IngredientsID' => $itemID,
                        'IngredientsDeviceNo' => $deleting_item['DeviceNo']
                    )
                );
                $is_bahan_used = $query_is_bahan_used->num_rows() > 0;
                if ($is_bahan_used) {
                    return "Item " . $namaItem . " tidak bisa dihapus karena digunakan oleh produk lain sebagai bahan.";
                }
            }
            /* Cek masteritemdelete*/
            $query_is_exist_in_delete_table = $this->_dbMaster->get_where(
                'masteritemdelete',
                array('DeviceID' => $idoutlet, 'ItemName' => $namaItem)
            );
            $exist_in_delete_table = $query_is_exist_in_delete_table->num_rows() > 0;

            if ($exist_in_delete_table) {
                $this->_dbMaster->where(array('ItemName' => $namaItem, 'DeviceID' => $idoutlet));
                $this->_dbMaster->delete('masteritemdelete');
            }


            $deleting_items[0]['HasBeenDownloaded'] = 0;
            $this->_dbMaster->insert('masteritemdelete', $deleting_items[0]);


            /*cek mastervariant delete */
            foreach ($deleting_items as $deleting_item) {
                $itemID = $deleting_item['ItemID'];
                $query_deleting_varian = $this->_dbMaster->get_where('mastervariant', array('DeviceID' => $idoutlet, 'ItemID' => $itemID));

                $deleting_varians = $query_deleting_varian->result_array();

                $query_is_exist_in_delete_table = $this->_dbMaster->get_where('mastervariantdelete', array('DeviceID' => $idoutlet, 'ItemID' => $itemID));
                $exist_in_deletevariant_table = $query_is_exist_in_delete_table->num_rows() > 0;
                if ($exist_in_deletevariant_table) {
                    $this->_dbMaster->where(array('ItemID' => $itemID, 'DeviceID' => $idoutlet));
                    $this->_dbMaster->delete('mastervariantdelete');
                }
                if (count($deleting_varians) > 0) {
                    for ($i = 0; $i < count($deleting_varians); $i++) {
                        $deleting_varians[$i]['HasBeenDownloaded'] = 0;

                        $deleted_variant = array(
                            "table" => 'deletemastervariant',
                            "column" => array(
                                "VarianID" => $deleting_varians[$i]['VarianID'],
                                "DeviceNo" => $deleting_varians[$i]['DeviceNo']
                            )
                        );
                        $this->load->model('Options');
                        $options = $this->Options->get_by_devid($idoutlet);
                        if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
                            $this->Firebasemodel->push_firebase(
                                $idoutlet,
                                $deleted_variant,
                                $deleting_varians[$i]['VarianID'],
                                $deleting_varians[$i]['DeviceNo'],
                                $perusahaanNo,
                                0
                            );
                        }
                    }

                    $this->_dbMaster->insert_batch('mastervariantdelete', $deleting_varians);
                    $sql_delete_variant = "DELETE FROM mastervariant WHERE DeviceID = " . $this->_dbMaster->escape($idoutlet) . " AND ItemID = " . $this->_dbMaster->escape($itemID) . ";";
                    $this->_dbMaster->query($sql_delete_variant);
                }
            }

            $deleted_data = array(
                "table" => 'deletemasteritem',
                "column" => array(
                    "ItemID" => $deleting_items[0]['ItemID'],
                    "DeviceNo" => $deleting_items[0]['DeviceNo']
                )
            );
            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            //Mengapa dikasih if?
            //if($options->CreatedVersionCode<200 && $options->EditedVersionCode<200) {
            $this->Firebasemodel->push_firebase(
                $idoutlet,
                $deleted_data,
                $deleting_items[0]['ItemID'],
                $deleting_items[0]['DeviceNo'],
                $perusahaanNo,
                0
            );
            //}

            $sql = "DELETE FROM masteritem WHERE DeviceID = " . $this->_dbMaster->escape($idoutlet) .
                " AND ItemName = " . $this->_dbMaster->escape($namaItem) . ";";
            $this->_dbMaster->query($sql);
        }

        return 1;
    }

    public function getAutocompleteBahan($idoutlet)
    {

        $this->db->where(array('DeviceID' => $idoutlet, 'IsProduct' => 'false'));
        $query = $this->db->get($this->_tableName);
        $result = $query->result();
        return $result;
    }

    public function updateCategoryID($CategoryID, $CategoryDeviceNo, $ItemID, $DeviceNo, $perusahaanNo, $idoutlet)
    {
        $this->initDbMaster();
        $query = $this->_dbMaster->get_where($this->_tableName, array(
            'PerusahaanNo' => $perusahaanNo, 'DeviceID' => $idoutlet, 'DeviceNo' => $DeviceNo, 'ItemID' => $ItemID
        ));
        $result = $query->row();
        if ($query->num_rows() > 0) {
            $itemid = $result->ItemID;
            $devno = $result->DeviceNo;
            $rowNumber = $this->getMaxRowNumber($idoutlet, $perusahaanNo, $CategoryID, $CategoryDeviceNo);
            $rvNew = (intval($result->RowVersion) + 1);

            $this->_dbMaster->update(
                $this->_tableName,
                array(
                    'CategoryID' => $CategoryID,
                    'CategoryDeviceNo' => $CategoryDeviceNo,
                    'RowNumber' => $rowNumber,
                    'RowVersion' => $rvNew
                ),
                array(
                    'PerusahaanNo' => $perusahaanNo, 'DeviceID' => $idoutlet,
                    'ItemID' => $itemid, 'DeviceNo' => $devno
                )
            );

            $query = $this->_dbMaster->get_where($this->_tableName, array(
                'PerusahaanNo' => $perusahaanNo, 'DeviceID' => $idoutlet,
                'ItemID' => $itemid, 'DeviceNo' => $devno
            ));
            $result = $query->row_array();
            // push data to firebase
            $last_update_data = array(
                "table" => $this->_tableName,
                "column" => $result
            );
            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
                $this->Firebasemodel->push_firebase(
                    $result['DeviceID'],
                    $last_update_data,
                    $result['ItemID'],
                    $result['DeviceNo'],
                    $result['PerusahaanNo'],
                    0
                );
            } else {
                $this->Firebasemodel->push_firebase(
                    $idoutlet,
                    array(
                        'table' => 'pleaseUpdateMasterItem',
                        'column' => array('ItemID' => $result['ItemID'], 'DeviceNo' => $result['DeviceNo'])
                    ),
                    $result['ItemID'],
                    $result['DeviceNo'],
                    $result['PerusahaanNo'],
                    0
                );
            }

            return $result->ItemID . "." . $result->DeviceNo;
        }
    }


    public function updateByName($oldName, $namaitem, $idkategori, $kategoridevno, $hargajual, $hargabeli, $isProduct, $punyabahan, $idoutlet, $namasatuan, $referensi, $fromBahanOrExtra)
    {
        $this->initDbMaster();
        $perusahaanNo = getPerusahaanNo();
        $this->_dbMaster->where(array('PerusahaanNo' => $perusahaanNo, 'DeviceID' => $idoutlet, 'ItemName' => $oldName));
        $query = $this->_dbMaster->get($this->_tableName);
        $result = $query->result();
        if (count($result) > 0) {
            $itemid = $result[0]->ItemID;
            $devno = $result[0]->DeviceNo;
            $oldCatID = $result[0]->CategoryID;
            $oldCatDevNo = $result[0]->CategoryDeviceNo;
            $rowNumber = $result[0]->RowNumber;
            $rvNew = $result[0]->RowVersion + 1;
            $columns_last_update_data = $query->row_array();
            $this->_dbMaster->where(array(
                'PerusahaanNo' => $perusahaanNo, 'DeviceID' => $idoutlet,
                'ItemID' => $itemid, 'DeviceNo' => $devno
            ));
            if ($fromBahanOrExtra == true) {
                $this->_dbMaster->update(
                    $this->_tableName,
                    array(
                        'ItemName' => $namaitem,
                        'Unit' => $namasatuan,
                        'PurchasePrice' => $hargabeli,
                        'DeviceID' => $idoutlet, 'Varian' => 'Nuta',
                        'HasBeenDownloaded' => 0,
                        'RowVersion' => $rvNew
                    )
                );
            } else {
                if ($isProduct == 'false' || ($isProduct == 'true' && $punyabahan == 'false'))
                    $usestock = 'true';
                else
                    $usestock = 'false';
                if ($oldCatID != $idkategori || $oldCatDevNo != $kategoridevno) {
                    $rowNumber = $this->getMaxRowNumber($idoutlet, $perusahaanNo, $idkategori, $kategoridevno);
                }

                $this->_dbMaster->update(
                    $this->_tableName,
                    array(
                        'ItemName' => $namaitem,
                        'CategoryID' => $idkategori,
                        'CategoryDeviceNo' => $kategoridevno,
                        'Unit' => $namasatuan,
                        'SellPrice' => $hargajual,
                        'PurchasePrice' => $hargabeli,
                        'IsProduct' => $isProduct,
                        'IsProductHasIngredients' => $punyabahan,
                        'Stock' => $usestock,
                        'DeviceID' => $idoutlet, 'Varian' => 'Nuta',
                        'HasBeenDownloaded' => 0,
                        'RowVersion' => $rvNew
                    )
                );
            }

            $this->_dbMaster->where(array(
                'PerusahaanNo' => $perusahaanNo, 'DeviceID' => $idoutlet,
                'ItemID' => $itemid, 'DeviceNo' => $devno
            ));
            $query = $this->_dbMaster->get($this->_tableName);
            $result = $query->result();
            // push data to firebase
            $last_update_data = array(
                "table" => $this->_tableName,
                "column" => $query->row_array()
            );
            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
                $this->Firebasemodel->push_firebase(
                    $idoutlet,
                    $last_update_data,
                    $last_update_data['column']['ItemID'],
                    $last_update_data['column']['DeviceNo'],
                    $perusahaanNo,
                    0
                );
            }

            return $result[0]->ItemID . "." . $result[0]->DeviceNo;
        } else {
            return $this->createNewItem($namaitem, $idkategori, $kategoridevno, $namasatuan, $hargajual, $hargabeli, $isProduct, $punyabahan, $idoutlet, getPerusahaanNo());
        }
    }

    public function updateLinkBahan($itemid, $idbahan, $qty, $deviceid, $perusahaanNo)
    {
        $realitemid = explode(".", $itemid)[0];
        $devno = explode(".", $itemid)[1];
        $realidbahan = explode(".", $idbahan)[0];
        $devnobahan = explode(".", $idbahan)[1];
        $this->initDbMaster();
        $this->_dbMaster->where(array(
            'ItemID' => $realitemid, 'ItemDeviceNo' => $devno,
            'IngredientsID' => $realidbahan, 'IngredientsDeviceNo' => $devnobahan, 'DeviceID' => $deviceid
        ));
        $this->_dbMaster->update('masteritemdetailingredients', array('QtyNeed' => $qty, 'HasBeenDownloaded' => 0));

        $query_dataupdated = $this->_dbMaster->get_where(
            'masteritemdetailingredients',
            array(
                'ItemID' => $realitemid, 'ItemDeviceNo' => $devno,
                'IngredientsID' => $realidbahan, 'IngredientsDeviceNo' => $devnobahan, 'DeviceID' => $deviceid
            )
        );

        $last_update_data = array(
            "table" => 'masteritemdetailingredients',
            "column" => $query_dataupdated->row_array()
        );
        $this->load->model('Options');
        $options = $this->Options->get_by_devid($deviceid);
        if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
            $this->Firebasemodel->push_firebase(
                $deviceid,
                $last_update_data,
                $last_update_data['column']['DetailID'],
                $last_update_data['column']['DeviceNo'],
                $perusahaanNo,
                0
            );
        }
    }

    public function hapusBahan($detailid, $deviceid, $devno, $perusahaanNo)
    {
        $this->initDbMaster();

        try {
            $this->_dbMaster->where(array(
                'DetailID' => $detailid, 'DeviceID' => $deviceid,
                'DeviceNo' => $devno
            ));
            $query_deleted_bahan_ex = $this->_dbMaster->get('masteritemdetailingredientsdelete');
            if (count($query_deleted_bahan_ex->result_array()) == 0) {
                $whr = array('DetailID' => $detailid, 'DeviceNo' => $devno, 'DeviceID' => $deviceid);
                $this->_dbMaster->where($whr);
                $query_deleted_bahan = $this->_dbMaster->get('masteritemdetailingredients');
                $deleted_bahan = $query_deleted_bahan->result_array();

                $deleted_bahan[0]['HasBeenDownloaded'] = 0;
                $this->_dbMaster->insert('masteritemdetailingredientsdelete', $deleted_bahan[0]);
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
        }
        $this->_dbMaster->where(array(
            'DetailID' => $detailid,
            'DeviceNo' => $devno, 'DeviceID' => $deviceid
        ));
        $this->_dbMaster->delete('masteritemdetailingredients');

        $deleted_data = array(
            "table" => 'deletemasteritemdetailingredients',
            "column" => array(
                "DetailID" => $detailid,
                'DeviceNo' => $devno
            )
        );
        $this->load->model('Options');
        $options = $this->Options->get_by_devid($deviceid);
        if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
            $this->Firebasemodel->push_firebase(
                $deviceid,
                $deleted_data,
                $detailid,
                $devno,
                $perusahaanNo,
                0
            );
        }
    }

    public function isItemUseInPendingSale($itemid, $deviceid)
    {
        $realitemid = explode(".", $itemid)[0];
        $devno = explode(".", $itemid)[1];
        $queryCekItemDiPesan = $this->_dbMaster->query('SELECT DISTINCT p.TransactionID, p.SaleNumber
                FROM saleitemdetail d INNER JOIN sale p
                ON p.PerusahaanNo=d.PerusahaanNo AND d.DeviceID=p.DeviceID
                AND p.TransactionID = d.TransactionID AND p.DeviceNo=d.TransactionDeviceNo
                WHERE p.PerusahaanNo= ' . getPerusahaanNo() . ' AND d.DeviceID=' . $deviceid .
            " AND p.Pending='true'" .
            ' AND ItemID=' . $realitemid .
            ' AND ItemDeviceNo=' . $devno);
        $count = $queryCekItemDiPesan->num_rows();

        return $count > 0;
    }

    public function isItemUseInProduk($itemidbahan, $deviceid)
    {
        $queryCekItemDiProduk = $this->_dbMaster->query('SELECT DISTINCT p.ItemName
                FROM masteritemdetailingredients d INNER JOIN masteritem p ON p.ItemID = d.ItemID AND d.DeviceID=p.DeviceID
                WHERE d.IngredientsID= ' . $this->_dbMaster->escape($itemidbahan) . ' AND d.DeviceID=' . $this->_dbMaster->escape($deviceid));
        $countProduk = $queryCekItemDiProduk->num_rows();
        if ($countProduk > 0) {
            //                return 'Item ini tidak dapat dihapus karena dipakai di Produk';
            return 'FAIL_USE_IN_PRODUCT';
        } else {
            return 'OK';
        }
    }

    public function hapusGambar($namaitem, $idoutlet)
    {
        $this->initDbMaster();
        $this->_dbMaster->where(array('ItemName' => $namaitem, 'DeviceID' => $idoutlet));
        $this->_dbMaster->update($this->_tableName, array('OnlineImagePath' => '', 'ImageLink' => '', 'HasBeenDownloaded' => 0));
    }

    public function updateImageLink($devid, $realitemid, $devno, $rowVersion)
    {
        $this->initDbMaster();
        $this->_dbMaster->where(array('DeviceID' => $devid, 'ItemID' => $realitemid, 'DeviceNo' => $devno)); // perlu where RowVersion?
        $this->_dbMaster->update($this->_tableName, array('ImageLink' => "/nuta/item$devid-$devno-$realitemid-$rowVersion.jpg"));
    }

    public function isInMultiOutlet($namaitem, $perusahaanID)
    {
        $query = $this->db->query("select OutletID from outlet where outletid in (select DeviceID from masteritem where ItemName="
            . $this->db->escape($namaitem) .
            ") AND PerusahaanID=" . $this->db->escape($perusahaanID));
        $result = $query->result();
        $retval = array();
        foreach ($result as $r) {
            array_push($retval, $r->OutletID);
        }
        return $retval;
    }

    /**
     * @param $idoutlet
     * @return int
     */
    public function getMaxItemID($idoutlet, $perusahaanNo)
    {
        $sql = "
        SELECT
          COALESCE (MAX(ItemID),0) +1 as id
        FROM
        (SELECT
            ItemID
        FROM
            masteritem
        WHERE
            PerusahaanNo = " . $perusahaanNo . " AND
            DeviceID = " . $this->db->escape($idoutlet) . " UNION ALL SELECT
            ItemID
        FROM
            masteritemdelete
        WHERE
            PerusahaanNo = " . $perusahaanNo . " AND
            DeviceID = " . $this->db->escape($idoutlet) . ") a
        ";

        $queryid = $this->db->query($sql);
        $resultid = $queryid->result();
        $itemid = $resultid[0]->id;
        return $itemid;
    }

    /**
     * @param $idoutlet
     * @return int
     */
    public function getMaxRowNumber($idoutlet, $perusahaanNo, $catID, $catDevNo)
    {
        $sql = "
        SELECT
          COALESCE (MAX(RowNumber),0) +1 as RowNumber
        FROM
        (SELECT
            RowNumber
        FROM
            masteritem
        WHERE
            PerusahaanNo = " . $perusahaanNo . " AND
            DeviceID = " . $idoutlet . " AND CategoryID = " . $catID . " AND CategoryDeviceNo=" . $catDevNo . "
        ) a";

        $queryid = $this->db->query($sql);
        $resultid = $queryid->result();
        $rownumber = $resultid[0]->RowNumber;
        return $rownumber;
    }

    public function getMaxIngredientDetailID($idoutlet)
    {
        $sql = "
        SELECT
          COALESCE (MAX(DetailID),0) +1 as id
        FROM
        (SELECT
            DetailID
        FROM
            masteritemdetailingredients
        WHERE
            DeviceID = " . $this->db->escape($idoutlet) . " UNION ALL SELECT
            DetailID
        FROM
            masteritemdetailingredientsdelete
        WHERE
            DeviceID = " . $this->db->escape($idoutlet) . ") a
        ";

        $queryid = $this->db->query($sql);
        $resultid = $queryid->result();
        $itemid = $resultid[0]->id;
        return $itemid;
    }

    public function getMaxModifierDetailID($idoutlet)
    {
        $sql = "
        SELECT
          COALESCE (MAX(DetailID),0) +1 as id
        FROM
        (SELECT
            DetailID
        FROM
            masteritemdetailmodifier
        WHERE
            DeviceID = " . $this->db->escape($idoutlet) . " UNION ALL SELECT
            DetailID
        FROM
            masteritemdetailmodifierdelete
        WHERE
            DeviceID = " . $this->db->escape($idoutlet) . ") a
        ";

        $queryid = $this->db->query($sql);
        $resultid = $queryid->result();
        $itemid = $resultid[0]->id;
        return $itemid;
    }

    public function getModifierIDByName($idoutlet, $namamodifier)
    {
        $this->initDbMaster();
        $this->_dbMaster->where(array('DeviceID' => $idoutlet, 'ModifierName' => $namamodifier));
        $query = $this->_dbMaster->get('mastermodifier');
        $count = $query->num_rows();
        if ($count > 0) {
            $result = $query->result();
            return $result[0]->ModifierID . "." . $result[0]->DeviceNo;
        }
        //        } else {
        //
        //            $sqlid = "
        //        SELECT
        //          COALESCE (MAX(ModifierID),0) +1 as id
        //        FROM
        //        (SELECT
        //            ModifierID
        //        FROM
        //            mastermodifier
        //        WHERE
        //            DeviceID = " . $this->db->escape($idoutlet) . " UNION ALL SELECT
        //            ModifierID
        //        FROM
        //           mastermodifierdelete
        //        WHERE
        //            DeviceID = " . $this->db->escape($idoutlet) . ") a";
        //            $queryid = $this->db->query($sqlid);
        //            $resultid = $queryid->result();
        //            $modifierid = $resultid[0]->id;
        //            $this->_dbMaster->insert('mastermodifier', array('ModifierID' => $modifierid, 'DeviceID' => $idoutlet, 'ModifierName' => $namamodifier, 'Varian' => 'Nuta',
        //                'HasBeenDownloaded' => 0
        //            ));
        //            return $modifierid;
        //        }
        return "-1.0";
    }

    public
    function createNewLinkModifier($idItem, $modifierID, $idoutlet)
    {
        $realitemid = explode(".", $idItem)[0];
        $devno = explode(".", $idItem)[1];
        $realmodifierid = explode(".", $modifierID)[0];
        $devnomodifier = explode(".", $modifierID)[1];
        $this->initDbMaster();
        $queryid = $this->db->query('select coalesce(max(DetailID),0)+1 as ID  FROM masteritemdetailmodifier WHERE DeviceID=' . $this->db->escape($idoutlet));
        $resultid = $queryid->row();
        $detailid = $resultid->ID;
        $perusahaanNo = getPerusahaanNo();

        $this->load->model('Options');
        $options = $this->Options->get_by_devid($idoutlet);
        $cloudDevno = 0;
        if ($options->CreatedVersionCode < 103 && $options->EditedVersionCode < 103) {
            $cloudDevno = 1;
        }

        $attrib_masteritemdetailmodifier = array(
            'DetailID' => $detailid,
            'DeviceNo' => $cloudDevno,
            'ItemID' => $realitemid,
            'ItemDeviceNo' => $devno,
            'ModifierID' => $realmodifierid,
            'ModifierDeviceNo' => $devnomodifier,
            'DeviceID' => $idoutlet, 'Varian' => 'Nuta',
            'HasBeenDownloaded' => 0,
            'PerusahaanNo' => $perusahaanNo
        );

        $result_insert_data_masteritemdetailmodifier = $this->_dbMaster->insert('masteritemdetailmodifier', $attrib_masteritemdetailmodifier);

        if ($result_insert_data_masteritemdetailmodifier) {
            //push to firebase
            $query_datainserted = $this->_dbMaster->get_where(
                'masteritemdetailmodifier',
                array(
                    'PerusahaanNo' => $perusahaanNo,
                    'DeviceID' => $idoutlet,
                    'DetailID' => $detailid,
                    'DeviceNo' => $cloudDevno,
                )
            );
            $last_insert_data = array(
                "table" => 'masteritemdetailmodifier',
                "column" => $query_datainserted->row_array()
            );
            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
                $this->Firebasemodel->push_firebase(
                    $idoutlet,
                    $last_insert_data,
                    $detailid,
                    $cloudDevno,
                    $perusahaanNo,
                    0
                );
            }
        }

        return $detailid;
    }

    public
    function getModifiersItem($idItem, $idOutlet)
    {
        $realitemid = explode(".", $idItem)[0];
        $devno = explode(".", $idItem)[1];
        $this->db->where(array('DeviceID' => $idOutlet, 'ItemID' => $realitemid, 'ItemDeviceNo' => $devno));
        $query = $this->db->get('masteritemdetailmodifier');
        return $query->result();
    }

    public
    function deleteModifierItem($idItem, $idOutlet, $detailid, $detaildevno, $perusahaanNo)
    {
        $realitemid = explode(".", $idItem)[0];
        $devno = explode(".", $idItem)[1];
        $this->initDbMaster();
        $this->_dbMaster->where(array(
            'DeviceID' => $idOutlet, 'ItemID' => $realitemid,
            'ItemDeviceNo' => $devno, 'DetailID' => $detailid, 'DeviceNo' => $detaildevno
        ));
        $this->_dbMaster->delete('masteritemdetailmodifier');

        $deleted_data = array(
            "table" => 'deletemasteritemdetailmodifier',
            "column" => array(
                "DetailID" => $detailid, 'DeviceNo' => $detaildevno
            )
        );
        $this->load->model('Options');
        $options = $this->Options->get_by_devid($idoutlet);
        if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
            $this->Firebasemodel->push_firebase(
                $idOutlet,
                $deleted_data,
                $detailid,
                $detaildevno,
                $perusahaanNo,
                0
            );
        }
    }

    public
    function deleteModifierLinks($idoutlet, $idmodifier, $perusahaanNo)
    {
        $realmodid = explode(".", $idmodifier)[0];
        $devno = explode(".", $idmodifier)[1];
        $this->initDbMaster();
        $this->_dbMaster->where(array(
            'DeviceID' => $idoutlet,
            'ModifierID' => $realmodid, 'ModifierDeviceNo' => $devno
        ));
        $q = $this->_dbMaster->get('masteritemdetailmodifier');
        $rows = $q->result_array();

        $arr = array();
        for ($a = 0; $a < count($rows); $a++) {
            $rows[$a]['HasBeenDownloaded'] = 0;
            $q2 = $this->_dbMaster->get_where('masteritemdetailmodifierdelete', array('DeviceID' => $idoutlet, 'DetailID' => $rows[$a]['DetailID']));
            $rows2 = $q2->result_array();
            if (count($rows2) == 0) {
                array_push($arr, $rows[$a]);
            }

            $deleted_data = array(
                "table" => 'deletemasteritemdetailmodifier',
                "column" => array(
                    "DetailID" => $rows[$a]['DetailID'],
                    "DeviceNo" => $rows[$a]['DeviceNo']
                )
            );
            $this->load->model('Options');
            $options = $this->Options->get_by_devid($idoutlet);
            if ($options->CreatedVersionCode < 200 && $options->EditedVersionCode < 200) {
                $this->Firebasemodel->push_firebase(
                    $idoutlet,
                    $deleted_data,
                    $rows[$a]['DetailID'],
                    $rows[$a]['DeviceNo'],
                    $perusahaanNo,
                    0
                );
            }
        }
        if (count($arr) > 0) {
            $this->_dbMaster->insert_batch('masteritemdetailmodifierdelete', $arr);
        }

        $this->_dbMaster->where(array('DeviceID' => $idoutlet, 'ModifierID' => $idmodifier));
        $this->_dbMaster->delete('masteritemdetailmodifier');
    }
}
