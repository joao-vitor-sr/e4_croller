<?php

namespace Files\Columns;

require_once __DIR__ . '/../../vendor/autoload.php';

use Files\Database\Connect;

class CsvUpdate extends Connect
{
	public $csvFile;
	public $logFile;

	private $ean;
	private $ncm;
	private $cest;
	private $natrec;
	private $cbenef;
	private $lojaTributo;
	private $pisConfins;

	private $eanId;
	private $ncmId;
	private $cestId;
	private $natrecId;
	private $cbenefId;
	private $lojaTributoId;
	private $pisConfinsId;

	private $rowFile;

	public function getEanCsvFile()
	{

		// Cleaning the log file 
		file_put_contents($this->logFile, "");

		$this->rowFile = 1;

		if (($fileCsv = fopen($this->csvFile, "r")) !== FALSE) {
			while (($data = fgetcsv($fileCsv, 1000, ";")) !== FALSE) {
				$this->setEan($data[1]);
				$this->setNcm($data[6]);
				$this->setCest($data[7]);
				$this->setNatrec($data[12]);
				$this->setCbenef($data[36]);
				$this->setPisConfins($data[11]);

				$this->setLojaTributo($data[24], $data[23]);

				$pdo = $this->getPdo();
				$queryUpdate = $pdo->prepare("UPDATE produtos SET ws_ncm = :ncm, ws_cest = :cest, ws_natureza_receita = :natrec, ws_ajustes_docto_fiscal = :cbenef, id_figura_fiscal = :lojas_tributacao, id_figura_fiscal_pis_cofins = :pis_confins WHERE id = :ean");
				$queryUpdate->bindValue(":ncm", $this->ncmId);
				$queryUpdate->bindValue(":cest", $this->cestId);
				$queryUpdate->bindValue(":natrec", $this->natrecId);
				$queryUpdate->bindValue(":cbenef", $this->cbenefId);
				$queryUpdate->bindValue(":lojas_tributacao", $this->lojaTributoId);
				$queryUpdate->bindValue(":pis_confins", $this->pisConfinsId);
				$queryUpdate->bindValue(":ean", $this->eanId);

				if ($this->lojaTributoId && $this->ncmId && $this->eanId) {
					$queryUpdate->execute();
				}

				echo "row - {$this->rowFile}" . PHP_EOL;

				$this->rowFile++;

			}
			fclose($fileCsv);
		}

	}

	// Log system
	public function log($message)
	{
		$fileLog = fopen($this->logFile, "a+");
		fwrite($fileLog, "\n" . $message);
		fclose($fileLog);
	}

	// Getters and setters
	public function setLojaTributo($lojaTributoY, $lojaTributoX)
	{
		$lojaTributoNumber;
		$lojaTributoResult;

		$characters = array('F', 'S', 'A');
		if ($lojaTributoX != 0) {
			$lojaTributoNumber = $lojaTributoX;
		} else {
			$lojaTributoNumber = NULL;
		}

		$lojaTributoResult = $lojaTributoY . $lojaTributoNumber; 

		$pdo = $this->getPdo();
		$querySelect = $pdo->prepare("SELECT * FROM lojas WHERE principal = :principal");
		$querySelect->bindValue(":principal", "S");
		$querySelect->execute();

		$resultQuerySelect = $querySelect->fetch();
		$idLojas = $resultQuerySelect['id'];

		$querySelect = $pdo->prepare("SELECT * FROM lojas_tributacao WHERE legenda = :lojaTributo AND id_lojas = :idLojas");
		$querySelect->bindValue(":lojaTributo", $lojaTributoResult);
		$querySelect->bindValue(":idLojas", $idLojas);
		$querySelect->execute();

		$resultQuerySelect = $querySelect->fetch(\PDO::FETCH_ASSOC);

		if ($resultQuerySelect) {
			$this->lojaTributoId = $resultQuerySelect['id_figura_fiscal'];
		} else if ($lojaTributoY == 'F' && !$resultQuerySelect) {
			foreach ($characters as $characterNow) {

				$lojaTributoResult = $characterNow . $lojaTributoNumber;

				$pdo = $this->getPdo();
				$querySelect = $pdo->prepare("SELECT * FROM lojas_tributacao WHERE legenda = :lojaTributo AND id_lojas = :idLojas");
				$querySelect->bindValue(":lojaTributo", $lojaTributoResult);
				$querySelect->bindValue(":idLojas", $idLojas);
				$querySelect->execute();

				if ($resultQuerySelect = $querySelect->fetch(\PDO::FETCH_ASSOC)) {
					$this->lojaTributoId = $resultQuerySelect['id_figura_fiscal'];
					break;
				} else {
					$this->lojaTributoId = NULL;
					continue;
				}
			}
		} else {
			$this->log("Nao foi possivel encontrar o id do seguinte loja tributo ({$lojaTributoResult}) na linha ({$this->rowFile})");
		}
	}

	public function setEan($ean)
	{
		if (!empty($ean)) {
			$this->ean = $ean;	
		} else {
			$this->ean = NULL;
			$ean = 0;
		}

		$pdo = $this->getPdo();
		$querySelect = $pdo->prepare("SELECT * FROM produtos_ean WHERE ean LIKE :ean");
		$querySelect->bindValue(":ean", "%" . $this->ean);
		$querySelect->execute();

		$resultQuerySelect = $querySelect->fetch(\PDO::FETCH_ASSOC);

		if ($resultQuerySelect) {
			$this->eanId = $resultQuerySelect['id_produtos'];
		} else {
			$this->eanId = NULL;
			$this->log("Nao foi possivel encontrar o id do ean ({$ean}) na linha ({$this->rowFile})");	
		}

	}

	public function setNcm($ncm) 
	{
		if (!empty($ncm)) {
			$this->ncm = $ncm;
		} else {
			$this->ncm = NULL;
			$this->ncmId = NULL;
		}

		$pdo = $this->getPdo();
		$querySelect = $pdo->prepare("SELECT * FROM ws_ncm WHERE codigo = :ncm");
		$querySelect->bindValue(":ncm", $this->ncm);
		$querySelect->execute();

		$resultQuerySelect = $querySelect->fetch(\PDO::FETCH_ASSOC);
		if ($resultQuerySelect) {
			$this->ncmId = $resultQuerySelect['id'];
		} else {
			$this->ncmId = NULL;

			if ($ncm != 0) {
				$this->log("Nao foi possivel encontrar o id do seguinte ncm ({$ncm}) na linha ({$this->rowFile})");
			}
		}
	}

	public function setCest($cest)
	{
		if (!empty($cest)) {
			$this->cest = $cest;
		} else {
			$this->cest = NULL;
			$this->cestId = NULL;
			$cest = 0;
		}

		$pdo = $this->getPdo();
		$querySelect = $pdo->prepare("SELECT * FROM ws_cest WHERE codigo = :cest");
		$querySelect->bindValue(":cest", $this->cest);
		$querySelect->execute();

		$resultQuerySelect = $querySelect->fetch(\PDO::FETCH_ASSOC);

		if ($resultQuerySelect) {
			$this->cestId = $resultQuerySelect['id'];
		} else {
			if ($cest != 0) {
				$this->log("Nao foi possivel encontrar o id do cest ({$cest}) na linha ({$this->rowFile})");
			}
		}


	}

	public function setNatrec($natrec)
	{
		if (!empty($natrec)) {
			$this->natrec = $natrec;
		} else {
			$this->natrec = NULL;
			$this->natrecId = NULL;
			$natrec = 0;
		}

		$pdo = $this->getPdo();
		$querySelect = $pdo->prepare("SELECT * FROM ws_natureza_receita WHERE codigo = :natrec");
		$querySelect->bindValue(":natrec", $this->natrec);
		$querySelect->execute();

		$resultQuerySelect = $querySelect->fetch(\PDO::FETCH_ASSOC);

		if ($resultQuerySelect) {
			$this->natrecId = $resultQuerySelect['id'];
		} else {
			if ($natrec != 0) {
				$this->log("Nao foi possivel encontrar o id do seguinte natrec ({$natrec}) na linha ({$this->rowFile})");
			}
		}
	}

	public function setCbenef($cbenef)
	{
		if (!empty($cbenef)) {
			$this->cbenef = $cbenef;
		} else {
			$this->cbenef = NULL;
			$this->cbenefId = NULL;
			$cbenef = 0;
		}

		$pdo = $this->getPdo();
		$querySelect = $pdo->prepare("SELECT * FROM ws_ajustes_docto_fiscal WHERE codigo = :cbenef");
		$querySelect->bindValue(":cbenef", $this->cbenef);
		$querySelect->execute();

		$resultQuerySelect = $querySelect->fetch(\PDO::FETCH_ASSOC);

		if ($resultQuerySelect) {
			$this->cbenefId = $resultQuerySelect['id'];
		} else {
			if ($cbenef != 0) {
				$this->log("Nao foi possivel encontrar o id do cbenef ({$cbenef}) na linha ({$this->rowFile})");
			}
		}
	}

	public function setPisConfins($pisConfins)
	{
		$this->pisConfins = $pisConfins;

		if ((mb_strlen($pisConfins)) == 2) {
			$pisConfinsFinal = $pisConfins;	
		} else {
			$pisConfinsFinal = 0 . $pisConfins;
		}

		$pdo = $this->getPdo();
		$selectPisConfinsId = $pdo->prepare("SELECT * FROM figura_fiscal_pis_cofins WHERE complemento LIKE :complement");
		$selectPisConfinsId->bindValue(':complement', "S" . $pisConfinsFinal . "%");
		$selectPisConfinsId->execute();

		if ($resultSelect = $selectPisConfinsId->fetch()) {
			$this->pisConfinsId = $resultSelect['id'];
		} else {
			if ($pisConfins != 0) {
				$this->log("Nao foi possivel encontrar o id do pis confins de saida ({$pisConfins}) com ean ({$this->ean}) na linha ({$this->rowFile})");
			}
		}
	}
}

