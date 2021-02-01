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

	private $statusIns;

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
				$this->setLojaTributo($data[24], $data[23], $data[4]);

				$dataParceiro = $data['38'] . date("H:i:s");

				$this->setStatusIns($data[4]);

				$this->updateTributacoesDivergentes();

				$pdo = $this->getPdo();
				$queryUpdate = $pdo->prepare("UPDATE produtos SET id_usuario_parceiro = :idUser, data_parceiro = :dateParceiro, status_inspector = :statusIns, ws_ncm = :ncm, ws_cest = :cest, ws_natureza_receita = :natrec, ws_ajustes_docto_fiscal = :cbenef, id_figura_fiscal = :lojas_tributacao, id_figura_fiscal_pis_cofins = :pis_confins WHERE id = :ean");
				$queryUpdate->bindValue(":idUser", $this->getUserParceiro());
				$queryUpdate->bindValue(":dateParceiro", $dataParceiro);
				$queryUpdate->bindValue(":statusIns", $this->statusIns);
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

	public function getUserParceiro()
	{
		$pdo = $this->getPdo();

		$querySelect = $pdo->query("SELECT * FROM usuarios WHERE nome = 'ADMINISTRADOR'");

		$queryResult = $querySelect->fetch(\PDO::FETCH_ASSOC);

		return !empty($queryResult['id']) ? $queryResult['id'] : null;

	}
	public function updateTributacoesDivergentes()
	{
		$pdo = $this->getPdo();

		$querySelect = $pdo->prepare("SELECT * FROM produtos WHERE id = :ean");
		$querySelect->bindValue(":ean", $this->eanId);
		$querySelect->execute();

		$querySelectResult = $querySelect->fetch(\PDO::FETCH_ASSOC);

		if ($querySelectResult) {
			$querySelect = $pdo->prepare("SELECT * FROM tributacoes_divergentes WHERE id_produtos = :id_produtos");
			$querySelect->bindValue(":id_produtos", $this->eanId);
			$querySelect->execute();

			$queryResult = $querySelect->fetch(\PDO::FETCH_ASSOC);

			if (!$queryResult) {
				$sql = "INSERT INTO tributacoes_divergentes (";
				$sql .= " id_produtos,";
				$sql .= " id_figura_fiscal,";
				$sql .= " id_figura_fiscal_pis_cofins,";
				$sql .= " ws_ncm,";
				$sql .= " ws_cest,";
				$sql .= " ws_natureza_receita,";
				$sql .= " id_figura_fiscal_atual,";
				$sql .= " id_figura_fiscal_pis_cofins_atual,";
				$sql .= " ws_ncm_atual,";
				$sql .= " ws_cest_atual,";
				$sql .= " ws_natureza_receita_atual";
				$sql .= " ) VALUES (";
				$sql .= " :id_produtos,";
				$sql .= " :id_figura_fiscal,";
				$sql .= " :id_figura_fiscal_pis_cofins,";
				$sql .= " :ws_ncm,";
				$sql .= " :ws_cest,";
				$sql .= " :ws_natureza_receita,";
				$sql .= " :id_figura_fiscal_atual,";
				$sql .= " :id_figura_fiscal_pis_cofins_atual,";
				$sql .= " :ws_ncm_atual,";
				$sql .= " :ws_cest_atual,";
				$sql .= " :ws_natureza_receita_atual";
				$sql .= ")";

				$queryInsert = $pdo->prepare($sql);
				$queryInsert->bindValue(":id_produtos", $this->eanId);
				$queryInsert->bindValue(":id_figura_fiscal", $querySelectResult['id_figura_fiscal']);
				$queryInsert->bindValue(":id_figura_fiscal_pis_cofins", $querySelectResult['id_figura_fiscal_pis_cofins']);
				$queryInsert->bindValue(":ws_ncm", $querySelectResult['ws_ncm']);
				$queryInsert->bindValue(":ws_cest", $querySelectResult['ws_cest']);
				$queryInsert->bindValue(":ws_natureza_receita", $querySelectResult['ws_natureza_receita']);
				$queryInsert->bindValue(":id_figura_fiscal_atual", $this->lojaTributoId);
				$queryInsert->bindValue(":id_figura_fiscal_pis_cofins_atual", $this->pisConfinsId);
				$queryInsert->bindValue(":ws_ncm_atual", $this->ncmId);
				$queryInsert->bindValue(":ws_cest_atual", $this->cestId);
				$queryInsert->bindValue(":ws_natureza_receita_atual", $this->natrecId);
				$queryInsert->execute();
			}
			// updating the 'atual' fields
			$sql = "UPDATE tributacoes_divergentes SET";
			$sql .= " ws_natureza_receita_atual = :ws_natureza_receita_atual,";
			$sql .= " ws_cest_atual = :ws_cest_atual,";
			$sql .= " ws_ncm_atual = :ws_ncm_atual,";
			$sql .= " id_figura_fiscal_pis_cofins_atual = :id_figura_fiscal_pis_cofins_atual,";
			$sql .= " id_figura_fiscal_atual = :id_figura_fiscal_atual";
			$sql .= " WHERE id_produtos = :ean";

			$queryUpdate = $pdo->prepare($sql);
			$queryUpdate->bindValue(":ws_natureza_receita_atual", $querySelectResult['ws_natureza_receita']);
			$queryUpdate->bindValue(":ws_cest_atual", $querySelectResult['ws_cest']);
			$queryUpdate->bindValue(":ws_ncm_atual", $querySelectResult['ws_ncm']);
			$queryUpdate->bindValue(":id_figura_fiscal_pis_cofins_atual", $querySelectResult['id_figura_fiscal_pis_cofins']);
			$queryUpdate->bindValue(":id_figura_fiscal_atual", $querySelectResult['id_figura_fiscal']);
			$queryUpdate->bindValue(":ean", $this->eanId);
			$queryUpdate->execute();
		}
	}

	public function setStatusIns($statusInsValue)
	{
		if (empty($this->statusIns)) {
			if ($statusInsValue == 'D') {
				$this->statusIns = 'D';
			} else if ($statusInsValue == 'P') {
				$this->statusIns = 'N';
			} else if ($statusInsValue == 'F') {
				if ($this->lojaTributoId) {
					$this->statusIns = 'C';
				} else {
					$this->statusIns = 'E';
				}
			}
		}
	}

	// Getters and setters
	public function setLojaTributo($lojaTributoY, $lojaTributoX, $statusCsv = '')
	{
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
