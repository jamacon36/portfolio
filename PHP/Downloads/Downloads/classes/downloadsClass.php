<?php
class Download
{
	public $target;
	public $downloadInfo;
	public $hostInfo;

	public function getHostsForCompatibility($target)
	{
		$dbloc = 'download';
		include $_SERVER['DOCUMENT_ROOT'] . '/_config/PDO/PDO.php';
		$host = $target;
		
		switch ($host) {
			case 'DaVinci Resolve':
				$host = 'DaVinci';
				break;
			case 'Canopus':
				$host = 'Grass Valley';
				break;
		}
		
		$compatability = array();
		$query = "SELECT * FROM Download_Compatible WHERE HostCompany = ?";
		$query = $pdo->prepare($query);
		$query->execute(array($host));
		
		while ($row = $query->fetch(PDO::FETCH_ASSOC))
		{
			$appStringQuery = "SELECT * FROM Download_Host WHERE HostCompany = :Company AND HostApp = :App AND HostVersionNumber = :Version AND HostArchitecture = :OS";
			$appStringQuery = $pdo->prepare($appStringQuery);
			$appStringQuery->bindParam(':Company', $host, PDO::PARAM_STR, 50);
			$appStringQuery->bindParam(':App', $row['HostApp'], PDO::PARAM_STR, 50);
			$appStringQuery->bindParam(':Version', $row['HostVersionNumber'], PDO::PARAM_INT);
			$appStringQuery->bindParam(':OS', $row['HostArchitecture'], PDO::PARAM_STR, 50);
			$appStringQuery->execute();
			
			while ($line = $appStringQuery->fetch(PDO::FETCH_ASSOC))
			{
				if (!empty($row['Product']))
				{
					$compatability[$row['HostApp']][$row['HostVersionNumber']]['VersionString'] = $line['HostVersionString'];
					$compatability[$row['HostApp']][$row['HostVersionNumber']]['VersionNum'] = $row['HostVersionNumber'];
					$compatability[$row['HostApp']][$row['HostVersionNumber']][$row['HostArchitecture']][$row['Product']] = $row['Product'] . ": " . $row['CompatibleProductVersions'];
				}
			}
		}
		
		return $compatability;
	}
	
	private function getHostsForDownload($product, $version, $host, $platform)
	{
		$dbloc = 'download';
		include $_SERVER['DOCUMENT_ROOT'] . '/_config/PDO/PDO.php';
		$query = "SELECT * FROM Download_Compatible WHERE Product = :Product AND Version = :Version AND Host = :Host AND Platform = :Platform";
		$query = $pdo->prepare($query);
		$query->bindParam(':Product', $product, PDO::PARAM_STR, 50);
		$query->bindParam(':Version', $version, PDO::PARAM_STR, 10);
		$query->bindParam(':Host', $host, PDO::PARAM_STR, 50);
		$query->bindParam(':Platform', $platform, PDO::PARAM_STR, 100);
		$query->execute();
		
		while ($row = $query->fetch(PDO::FETCH_ASSOC))
		{
			$appStringQuery = "SELECT * FROM Download_Host WHERE HostCompany = :Company AND HostApp = :App AND HostVersionNumber = :Version AND HostArchitecture = :OS";
			$appStringQuery = $pdo->prepare($appStringQuery);
			$appStringQuery->bindParam(':Company', $host, PDO::PARAM_STR, 50);
			$appStringQuery->bindParam(':App', $row['HostApp'], PDO::PARAM_STR, 50);
			$appStringQuery->bindParam(':Version', $row['HostVersionNumber'], PDO::PARAM_INT);
			$appStringQuery->bindParam(':OS', $row['HostArchitecture'], PDO::PARAM_STR, 50);
			$appStringQuery->execute();
			
			$appAssoc = $appStringQuery->fetch(PDO::FETCH_ASSOC);
			$hostversionstring = $appAssoc['HostVersionString'];
		}
		
	}
	
	private function getDownloads()
	{
		$dbloc = 'download';
		include $_SERVER['DOCUMENT_ROOT'] . '/_config/PDO/PDO.php';
		$downloads = array();
		$count = 0;
		
		$Action = $this->target;
		$bigActions = array('esd_bcctrials' => 'Demo', 'updates' => 'Update', 'store' => 'Store');
		$query = '';
		
		if (array_key_exists($Action, $bigActions))
		{
			if ($Action = 'esd_bcctrials')
			{
				$query = "SELECT * FROM Download_Action WHERE Action = ?";
			}
			else 
			{
				$query = "SELECT * FROM Download_Action WHERE Lead_Source = ?";
				$Action = $bigActions[$Action];
			}
		}
		else
		{
			$query = "SELECT * FROM Download_Action WHERE Action = ?";
		}
		
		$query = $pdo->prepare($query);
		$query->execute(array($Action));
		while ($action = $query->fetch(PDO::FETCH_ASSOC))
		{
			if ($action['Lead_Source'] == 'Web')
			{
				$downloads['singleDownload'] = true;
				$download['header'] = $action['Download_Id_Array'];
				
				$getFile = array(
						':Product' => $Action,
						':Version' => $Action,
						':Host' => $Action,
						':Platform' => $Action,
						':Type' => 'File'
				);
				$Id = "SELECT * FROM Downloads WHERE Product = :Product AND Version = :Version AND Host = :Host AND Platform = :Platform AND Type = :Type";
				$Id = $pdo->prepare($Id);
				$Id->execute($getFile);
				$Id = $Id->fetch(PDO::FETCH_ASSOC);
				
				$file = "SELECT File_Name FROM Download_File WHERE File_Id = ?";
				$file = $pdo->prepare($file);
				$file->execute(array($Id['File_Id']));
				$file = $file->fetch(PDO::FETCH_ASSOC);
				$file = $file['File_Name'];
				
				$download['url'] = $file;
				$download['DID'] = 1;
				array_push($downloads, $download);
			}
			else
			{
				$downloadId = unserialize($action['Download_Id_Array']);
				foreach ($downloadId as $option)
				{
					$download['signedURL'] = ($action['Lead_Source'] == 'Demo' || $action['Lead_Source'] == 'Update');
					$getFile = array(
							':Product' => $option['Product'],
							':Version' => $option['Version'],
							':Host' => $option['Host'],
							':Platform' => $option['Platform'],
							':Type' => $option['Type']
					);
					$Id = "SELECT * FROM Downloads WHERE Product = :Product AND Version = :Version AND Host = :Host AND Platform = :Platform AND Type = :Type";
					$Id = $pdo->prepare($Id);
					$Id->execute($getFile);
					$Id = $Id->fetch(PDO::FETCH_ASSOC);
					
					$file = "SELECT File_Name FROM Download_File WHERE File_Id = ?";
					$file = $pdo->prepare($file);
					$file->execute(array($Id['File_Id']));
					$file = $file->fetch(PDO::FETCH_ASSOC);
					$file = $file['File_Name'];
				
					if ($Id['Redirect_Id'])
					{
						$redirect = "SELECT File_Name FROM Download_Redirect WHERE Redirect_Id = ?";
						$redirect = $pdo->prepare($redirect);
						$redirect->execute($Id['Redirect_Id']);
						$redirect = $redirect->fetch(PDO::FETCH_ASSOC);
						$redirect = $redirect['Redirect_URL'];
					}
					else
					{
						$redirect = false;
					}
				
					$host = $option['Host'];
				
					//$hostDetails = $this->getHostsForDownload($product, $version, $host, $platform);
				
					$count++;
					
					$download['file'] = $file;
					$download['redirect'] = $redirect;
					$download['product'] = $option['Product'];
					$download['host'] = $option['Host'];
					$download['hostData'] = $option['Host_String'];
					$download['type'] = $action['Lead_Source'];
					$download['version'] = $option['Version'];
					$download['DID'] = $count;
					$download['platform'] = $option['Platform'];
					array_push($downloads, $download);
				}
			}
		}
		
		if (isset($_COOKIE['bfxdownload']))
			unset($_COOKIE['bfxdownload']);
		
		$cData = json_encode($downloads);
		
		setcookie('bfxdownload', $cData, time() + 3600, '/', 'borisfx.com');
		
		return $downloads;
	}
	
	public function __construct($target)
	{
		if ($target)
		{
			$this->target = $target;
			$this->downloadInfo = $this->getDownloads();
		}
	}
}