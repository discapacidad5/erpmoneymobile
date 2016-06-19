<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class bono_mobile_money extends CI_Model
{

	private $totalPorIgualacionParaDar;
	private $totalQueSobraParaDar;
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('/bo/bonos/afiliado');

	}
	
	public function setUpBono($id_red, $fecha_inicio, $fecha_fin){

		$totalPorIgualacion=$this->getTotalPorIgualacion($id_red, $fecha_inicio, $fecha_fin);
		$totalQueSobra=$this->getTotalPorIgualacionQueSobra($id_red, $fecha_inicio, $fecha_fin);

		$this->setTotalPorIgualacionParaDar($totalPorIgualacion);
		$this->setTotalQueSobraParaDar($totalQueSobra);
	}
	
	function getAfiliadosPataDebil($id_red,$id_afiliado,$val){
                
		$usuario=new $this->afiliado();
		$totalAfiliados=$usuario->getAfiliadosIntervaloDeTiempo($id_afiliado,$id_red,"RED","DEB",0,"2016-01-01","2020-01-01",$val);
                /*echo $id_afiliado<>2 ? " " : $id_afiliado."-"." valor:".$totalAfiliados." ";*/
		return $totalAfiliados;
	}
	
	function getTotalARepartir($id_red,$id_afiliado,$fecha_inicio,$fecha_fin){
		$usuario=new $this->afiliado();
		$usuario->getAfiliadosDebajoDe($id_afiliado,$id_red,"RED",0,0);
		$afiliadosRed=$usuario->getIdAfiliadosRed();
		$totalARepartir=0;
		$totalDePuntos=0;
		
		foreach ($afiliadosRed as $id_afiliado) {
			$totalPuntos=$usuario->getPuntosTotalesPersonalesIntervalosDeTiempo($id_afiliado,$id_red,"0","0",$fecha_inicio,$fecha_fin)[0]->total;
			$totalPuntos=intval($totalPuntos);
			$totalDePuntos=$totalDePuntos+$totalPuntos;
			if(($totalPuntos>=3)&&($totalPuntos<9)){
				$totalARepartir=$totalARepartir+40;
			}else if(intval($totalPuntos)>=9){
				$totalARepartir=$totalARepartir+300;
			}
		} 

		return $totalARepartir;
	}
	
	function getTotalIgualaciones($id_red,$id_nodo_raiz){
		$usuario=new $this->afiliado();
		$usuario->getAfiliadosDebajoDe($id_nodo_raiz,$id_red,"RED",0,0);
		$afiliadosRed=$usuario->getIdAfiliadosRed();
		$totalIgualaciones=0;
		
		foreach ($afiliadosRed as $id_afiliado) {
			$totalIgualaciones=$totalIgualaciones+$this->getAfiliadosPataDebil($id_red,$id_afiliado,1);
		}
		
		return $totalIgualaciones;
	}
	
	function getTotalPorIgualacion($id_red,$fecha_inicio,$fecha_fin){
		$id_nodo_raiz=1;
		$getTotalARepartir = $this->getTotalARepartir($id_red, $id_nodo_raiz,$fecha_inicio,$fecha_fin);
		$getTotalIgualaciones = $this->getTotalIgualaciones($id_red, $id_nodo_raiz);
		return ($getTotalIgualaciones==0) ? 0 : $getTotalARepartir/$getTotalIgualaciones;
	}

	function getTotalAfiliadosBono3Puntos($id_red,$totalRepartirPorIgualacion,$fecha_inicio,$fecha_fin){
		$usuario=new $this->afiliado();
		$usuario->getAfiliadosDebajoDe(2,$id_red,"RED",0,0);
		$afiliadosRed=$usuario->getIdAfiliadosRed();
		$valor=0;
		$numero_de_igualaciones=0;
		
		foreach ($afiliadosRed as $id_afiliado) {
			$totalPuntos=$usuario->getPuntosTotalesPersonalesIntervalosDeTiempo($id_afiliado,$id_red,"0","0",$fecha_inicio,$fecha_fin)[0]->total;
			$totalPuntos=intval($totalPuntos);
			if(($totalPuntos>=3)&&($totalPuntos<9)){
				$totalAfiliados=$usuario->getAfiliadosIntervaloDeTiempo($id_afiliado,$id_red,"RED","DEB",0,"2016-01-01","2020-01-01",1);
				if($totalAfiliados>0){
					$numero_de_igualaciones=$numero_de_igualaciones+$totalAfiliados;
					$valorTotalPorIgualacion=$totalRepartirPorIgualacion*$numero_de_igualaciones;
					$totalAReclamar=($valorTotalPorIgualacion)/7.5;
					$valor=$valor+$valorTotalPorIgualacion-$totalAReclamar;
				}
			}
		}
		
		$datosAfiliadosBono3Puntos = array(
				'valor' =>$valor,
				'numero_igualaciones'   => $numero_de_igualaciones,
		);
		
		return $datosAfiliadosBono3Puntos;
	}
	
	function getTotalPorIgualacionQueSobra($id_red,$fecha_inicio,$fecha_fin){
		$id_nodo_raiz=2;
		
		$totalRepartirPorIgualacion=$this->getTotalPorIgualacion($id_red,$fecha_inicio,$fecha_fin);
		$datos=$this->getTotalAfiliadosBono3Puntos($id_red,$totalRepartirPorIgualacion,$fecha_inicio,$fecha_fin);
		$totalDeIgualaciones=$this->getTotalIgualaciones($id_red,$id_nodo_raiz);
		$totalIgualacionesRestantes=$totalDeIgualaciones-$datos["numero_igualaciones"];
		
		return ($totalIgualacionesRestantes==0) ? 0 : $datos["valor"]/$totalIgualacionesRestantes;
	}
	
	function getTotalARecibirAfiliado($id_red,$id_afiliado){
		$usuario=new $this->afiliado();
		$totalPuntos=$usuario->getPuntosTotalesPersonalesIntervalosDeTiempo($id_afiliado,$id_red,"0","0","2016-01-01","2020-01-01")[0]->total;
		$totalPuntos=intval($totalPuntos);
		$totalARecibir=0;

		$igualaciones=$this->getAfiliadosPataDebil($id_red,$id_afiliado,2);
		
		$totalPorIgualacion=$this->getTotalPorIgualacionParaDar();
		$totalQueSobra=$this->getTotalQueSobraParaDar();
                //echo "igualaciones:".$igualaciones;//exit();
		if(($totalPuntos>=6)&&($totalPuntos<9)&&($igualaciones>0)){
			$totalARecibir=($totalPorIgualacion*$igualaciones)/7.5;
		}else if(intval($totalPuntos)>=9&&($igualaciones>0)){//echo $totalPorIgualacion;exit();
			$totalARecibir=($totalPorIgualacion*$igualaciones)+($totalQueSobra*$igualaciones);
		}
		
		return $totalARecibir;
	}
	public function getTotalPorIgualacionParaDar() {
		return $this->totalPorIgualacionParaDar;
	}
	public function setTotalPorIgualacionParaDar($totalPorIgualacionParaDar) {
		$this->totalPorIgualacionParaDar = $totalPorIgualacionParaDar;
		return $this;
	}
	public function getTotalQueSobraParaDar() {
		return $this->totalQueSobraParaDar;
	}
	public function setTotalQueSobraParaDar($totalQueSobraParaDar) {
		$this->totalQueSobraParaDar = $totalQueSobraParaDar;
		return $this;
	}
	
	
}
