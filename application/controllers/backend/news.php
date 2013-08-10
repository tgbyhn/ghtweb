<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class News extends Controllers_Backend_Base
{
    /**
     * @var array: Названия полей которые будут браться из $_GET для поиска
     */
    private $_search_fields = array('title');
    
    
    
    public function __construct()
    {
        parent::__construct();
        
        $class = strtolower(__CLASS__);
        $this->_model = $class . '_model';
        $this->_view  = $class;  
        
        $this->load->model($this->_model);
    }
    
    public function index()
    {
        $limit = (int) $this->config->item('news_per_page', 'backend');

        
        $data_db_like = array();
        
        // Поиск
        if($this->input->get())
        {
            $get = $this->input->get();
            
            foreach($get as $key => $val)
            {
                $val = trim($val);
                
                if(in_array($key, $this->_search_fields) && $val != '')
                {
                    $data_db_like[$key] = $val;
                }
            }
        }
        
        $count = $this->{$this->_model}->get_count(NULL, $data_db_like);
        
        // Пагинация
        $this->load->library('pagination');

        Pagination::initialize(array(
            'base_url'   => '/backend/news/',
            'total_rows' => $count,
            'per_page'   => $limit,
        ));

        $view_data = array(
            'pagination' => Pagination::create_links(),
            'content'    => $this->{$this->_model}->get_list(Pagination::$offset, $limit, NULL, 'created_at', 'DESC', $data_db_like),
            'count'      => $count,
            'offset'     => Pagination::$offset,
        );

        $this->view_data['content'] = $this->load->view('news/index', $view_data, TRUE);
    }
    
    public function edit()
    {
        $id = (int) $this->uri->segment(4);
        
        if($id < 1)
        {
            redirect('backend/' . $this->_view);
        }
        
        $data_db_where = array(
            'id' => $id
        );
        
        
        // Save
        if(isset($_POST['submit']))
        {
            $this->load->library('form_validation');
            
            $this->form_validation->set_error_delimiters('', '');
            
            if($this->form_validation->run('backend_news'))
            {
                $data_db           = elements($this->{$this->_model}->get_fields(), $this->input->post(), NULL);
                $data_db['author'] = $this->auth->get('login', NULL);

                if($this->{$this->_model}->edit($data_db, $data_db_where, 1))
                {
                    $this->cache->delete('news/' . $id);
                    $message = Message::true('Новость сохранена');
                }
                else
                {
                    $message = Message::false('Ошибка! Не удалось записать данные в БД');
                }
            }
        }

        $view_data = array(
            'content' => $this->{$this->_model}->get_row($data_db_where),
            'message' => isset($message) ? $message : '',
        );

        $this->view_data['content'] = $this->load->view('news/edit', $view_data, TRUE);
    }
    
    public function add()
    {
        if(isset($_POST['submit']))
        {
            $this->load->library('form_validation');
            
            $this->form_validation->set_error_delimiters('', '');
            
            if($this->form_validation->run('backend_news'))
            {
                $data_db           = elements($this->{$this->_model}->get_fields(), $this->input->post(), NULL);
                $data_db['author'] = $this->auth->get('login', NULL);
                
                if($this->{$this->_model}->add($data_db))
                {
                    $message = Message::true('Новость добавлена');
                }
                else
                {
                    $message = Message::false('Ошибка! Не удалось записать данные в БД');
                }
            }
        }

        $view_data = array(
            'message' => isset($message) ? $message : '',
        );

        $this->view_data['content'] = $this->load->view('news/add', $view_data, TRUE);
    }
    
    public function del()
    {
        $id = (int) $this->uri->segment(4);
        
        if($id < 1)
        {
            redirect('backend/' . $this->_view);
        }
        
        $data_db_where = array(
            'id' => $id
        );
        
        $this->{$this->_model}->del($data_db_where, 1);
        
        $this->cache->delete('news/' . $id);
        
        $this->session->set_flashdata('message', Message::true('Новость удалена'));
        redirect('backend/' . $this->_view);
    }
    
    public function stop()
    {
        $id = (int) $this->uri->segment(4);
        
        if($id < 1)
        {
            redirect('backend/' . $this->_view);
        }
        
        $allow = ($this->uri->segment(5) == 'off' ? '0' : '1');
        
        $data_db_where = array(
            'id' => $id
        );
        
        $data_db = array(
            'allow' => $allow,
        );
        
        $this->{$this->_model}->edit($data_db, $data_db_where);
        
        $this->cache->delete('news/' . $id);

        $msg = ($allow == 1 ? 'вкл' : 'выкл');
        $this->session->set_flashdata('message', Message::true($msg));
        redirect('backend/' . $this->_view);
    }
}