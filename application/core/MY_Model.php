<?php
defined("BASEPATH") OR exit('No direct script access allowed');
class MY_Model extends CI_Model
{
    /**
     * Definisi nama tabel
     *
     * @var $table
     */
    protected $table = null;
    /**
     * Definsisi property class
     *
     * @var $primary_key
     */
    protected $primary_key = "id";
    /**
     * Method untuk mengambil semua
     *
     * @param $sort
     * @param $by
     * @param $limit
     * @param $offset
     *
     * @return CI_DB_active_record
     */
    public function all($sort = "created_at", $by = "desc", $limit = 15, $offset = 0)
    {
        $this->db->order_by($sort, $by);
        $this->db->limit($limit);
        $this->db->offset($offset);
        return $this->db->get($this->table);
    }
    /**
     * Insert data ke tabel
     * @param array $attributes
     *
     * @return CI_DB_active_record
     */
    public function create($attributes)
    {
        // $this->output->enable_profiler(TRUE);

        return $this->db->insert($this->table, $attributes);
    }
    /**
     * Ambil satu data dari tabel
     *
     * @param int $primary_key
     *
     * @return CI_DB_active_record
     */
    public function find($primary_key)
    {
        return $this->db
            ->where($this->primary_key, $primary_key)
            ->get($this->table)
            ->row();
    }
    /**
     * Ambil satu data dari tabel, jika tidak ada menampilkan halaman 404
     *
     * @param $primary_key
     *
     * @return mixed|CI_DB_active_record
     */
    public function find_or_fail($primary_key)
    {
        $row = $this->find($primary_key);
        return $row ? $row : show_404();
    }
    /**
     * @param mixed $field|$attributes
     * @param $value
     *
     * @return CI_DB_active_record
     */
    public function where ($field, $value = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->db->where($key, $val);
            }
        } else {
            $this->db->where($field, $value);
        }
        return $this->db->get($this->table);
    }
    /**
     * Update data dari tabel
     *
     * @param $where_attr
     * @param array $attributes
     *
     * @return CI_DB_active_record
     */
    public function update($where_attr = [], $attributes)
    {
        return $this->db->update($this->table, array_merge($where_attr, $attributes), $where_attr);
    }
    /**
     * Hapus data dari tabel
     *
     * @param $where_attr
     *
     * @return CI_DB_active_record
     */
    public function delete ($where_attr = [])
    {
        return $this->db->delete($this->table, $where_attr);
    }
}