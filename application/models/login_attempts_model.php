<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Login_attempts_model extends Crud
{
    public $table = 'login_attempts';


    /**
     * Добавляет +1 к неудачной попытки входа
     *
     * @return void
     */
    public function add_false_attempt()
    {
        $this->db->query("INSERT INTO `" . $this->db->dbprefix . $this->table . "` (`ip`, `created_at`, `count`) VALUES(?, ?, 1) ON DUPLICATE KEY UPDATE `count` = count+1, created_at = '" . db_date() . "'", array(
            $this->input->ip_address(), db_date()
        ));
    }

    /**
     * Возвращает данные о неудачных попытках входа
     *
     * @return array
     */
    public function get_data()
    {
        $this->db->where('ip', $this->input->ip_address());
        return $this->db->get($this->table, 1)->row_array();
    }

    /**
     * Очистка данных
     *
     * @return boolean
     */
    public function clear()
    {
        return $this->db->delete($this->table, array('ip' => $this->input->ip_address()), 1);
    }
}