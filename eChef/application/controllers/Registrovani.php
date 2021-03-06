<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Registrovani
 *
 * @author Korisnik
 */
class Registrovani extends CI_Controller {
    public function __construct() {
           parent::__construct();
           $this->load->model('Jelo');
           $this->load->model('Korisnik');
           
          if (($this->session->userdata('korisnik')) == NULL) {
            redirect("Gost");
          }
          if ($this->session->userdata('korisnik')->oznaka == 'a') {
            redirect("Admin");
          }
          if ($this->session->userdata('korisnik')->oznaka == 'k'){
                 redirect("Kuvar");
          }
		 
    }
    // samo poziva prikazi sa drugim podacima..
    //isto kao i kod gosta samo se loaduje novi novi header.  
    public function index(){
       $podaci=[];
       $this->prikazi("home.php",$podaci);
    }
    
    public function pretraga(){
        //uzme podatak 
        $search = $this->input->get('searchBox');
        $data = [];
        $data['jela'] = $this->Jelo->dohvatiJeloIme($search);
        $this->prikazi("rezultatipretrage.php",$data);
    }
    
    
    public function prikazipretraga(){
        $data=[];
        $this->prikazi("search.php",$data);
    }
    
//funkcija vrsi prelog jela za odgovarajuceg korisnika na osnovu njegovih preferiranih ukusa/ocena
    //prikazuje view sa odabranim jelom
    public function predloziJelo() {
        $flag = 0;
        while ($flag == 0) {
            $idK=$this->session->userdata('korisnik')->idK;
            $jela = $this->Jelo->getOmiljenaJela($idK);
            $size = count($jela);
            $ind = mt_rand(0, $size - 1);

            /* ob_flush();
              ob_start();
              var_dump($size);
              file_put_contents('dump.txt', ob_get_flush());

              ob_flush();
              ob_start();
              var_dump($ind);
              file_put_contents('dump.txt', ob_get_flush());

              ob_flush();
              ob_start();
              var_dump($jela[$ind]->idR);
              file_put_contents('dump.txt', ob_get_flush()); */
            $idR = $jela[$ind]->idR;
            if($this->Jelo->getAlergije($idK)){
                if($this->Jelo->proveriRecept($idR,$idK))
                   $flag=1;
            }
            else $flag=1;
        }
        $this->prikaziJelo($idR);
    }    

	//funkcija vrsi odabir 3 jela na osnovu ukusa konkretnog korisnika
	//i prikazuje ih na posebnom view-u za meni
   /* public function izradiPlanIshrane(){
       $data=[];
       $data['jela']=[];
        $data['jela'][0] = $this->Jelo->dohvatiJeloId($this->Jelo->getOmiljenoJelo($this->session->userdata('korisnik')->idK,"Breakfast"))[0];
        $data['jela'][1] = $this->Jelo->dohvatiJeloId($this->Jelo->getOmiljenoJelo($this->session->userdata('korisnik')->idK,"Lunch"))[0];
        $data['jela'][2] = $this->Jelo->dohvatiJeloId($this->Jelo->getOmiljenoJelo($this->session->userdata('korisnik')->idK,"Dinner"))[0];
       $this->prikazi("menu.php",$data);
    }*/
    public function izradiPlanIshrane(){
       $data=[];
       
       $data['jela']=[];
       
       $data=$this->Korisnik->izradiMeni($data);
      
       $this->prikazi("menu.php",$data);
    }

    
    
	//funkcija vrsi prikaz knjige konkrentog korisnika
     public function knjiga(){
        $data = [];
        $data['jela'] = $this->Korisnik->knjiga($this->session->userdata('korisnik')->idK);
        $this->prikazi("book-2-column.php",$data);
     }

    public function oceni($idK,$ocena,$idR)
    {
        $this->Jelo->dodajOcenu($idK,$ocena,$idR);
       redirect(site_url("Registrovani/prikaziJelo/".$idR));
    }
	
	//funckija dodaje komentar koji je korisnik uneo za konkretan recept
	//i nakon unosa vrsi prikaz tog recepta
    public function dodajKomentar()
    { 
        $data = array
        (
            'idKorisnika'=> $this->session->userdata('korisnik')->idK,
            'sadrzaj' => $this->input->get('comment'),
            'idR' => $this->input->get('idr')
        );
        
        $this->Jelo->dodajKomentar($data);
        redirect(site_url("Registrovani/prikaziJelo/".$data['idR']));
    }
	
	
    //funkcija uklanja komentar koji je korisnik uneo
    public function ukloniKomentar($idK,$idR){
        $this->Jelo->ukloniKomentar($idK);
        redirect(site_url("Registrovani/prikaziJelo/".$idR));
    }
    //funkcija vrsi dodavanje recepta u knjigu omiljenih korisnika koji ju je inicirao
	//@param id - id recepta koji se dodaje u knjigu
    public function dodajUKnjigu($id){
       $this->Korisnik->dodajUKnjigu($this->session->userdata('korisnik')->idK,$id);
       redirect(site_url("Registrovani/prikaziJelo/".$id));    
     }
    public function ukloniIzKnjige($id){
        $this->Korisnik->ukloniIzKnjige($this->session->userdata('korisnik')->idK,$id);
        redirect(site_url("Registrovani/prikaziJelo/".$id)); 
    }
    private function prikazi($glavniDeo, $data){
       
        $data['controller']='Registrovani';
        $this->load->view("sablon/registrovan_header.php", $data);
        $this->load->view($glavniDeo, $data);
        $this->load->view("sablon/footer.php");
    }
	
	
    //funkcija za izlogovanje korisnika i gasenje njegove sesije
	//nakon toga korisnik se prosledjuje kontroleru Gost
    public function logout(){
        $this->session->unset_userdata("korisnik");// brise se podatak o autoru iz sesije
        $this->session->sess_destroy(); //brise se sesija
        redirect("Gost");//kako vise nije ulogovan, treba da se ponasa kao sto je definisano u kontroleru gost
    }

     public function prikaziJelo($id){
     $data=[];
     $data['jelo']=$this->Jelo->dohvatiJeloId($id);
     $data['komentari']= $this->Jelo->dohvatiKomentareJela($id);
     $data['ocene']= $this->Jelo->dohvatiOceneJela($id);
     $this->prikazi("recipe_demo.php",$data);
    }
    public function prikaziPrilika($prilika){
        $data = [];
        $data['jela'] = $this->Jelo->dohvatiJeloPrilika($prilika);
        $this->prikazi("rezultatipretrage.php",$data);
    }
    
    public function prikaziPoSastojku($sastojak){
        $data = [];
        $data['jela'] = $this->Jelo->dohvatiJeloSastojak($sastojak);
        $this->prikazi("rezultatipretrage.php",$data);
    }
    
     public function prikaziKategoriju($kategorija){
        $data = [];
        $data['jela'] = $this->Jelo->dohvatiJeloKategorija($kategorija);
        $this->prikazi("rezultatipretrage.php",$data);
    }
}
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
     

