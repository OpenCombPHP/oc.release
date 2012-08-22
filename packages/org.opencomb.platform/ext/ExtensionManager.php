<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\lang\Exception;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\lang\Object;
use org\opencomb\platform as oc;

class ExtensionManager extends Object
{
	/**
	 * @example /配置/读取item
	 * @forwiki /配置
	 * @forclass org\opencomb\platform\ext\ExtensionMetainfo
	 * @formethod __construct
	 * @param Setting $aSetting
	 */
	public function __construct(Setting $aSetting=null)
	{
		if(!$aSetting)
		{
			// 取得 Setting 的单例对象
			$aSetting = Setting::singleton() ;
		}
		
		$this->arrInstalledExtensions = array() ;
		
		// 取得Setting中的item数据：已安装扩展的路径数组
		// 如果指定的item不存在返回 null （该item以数组形式保存在 setting中） 。
		foreach( $aSetting->item("/extensions",'installeds')?: array()  as $sExtPath )
		{
			try{
				$aExtension = ExtensionMetainfo::load(
						oc\EXTENSIONS_FOLDER.'/'.$sExtPath
						, oc\EXTENSIONS_URL.'/'.$sExtPath
				) ;
			} catch (\Exception $e) {
				throw new Exception("保存在 service 的 setting 中的扩展路径无效：%s，扩展路径必须是 extensions 目录下的相对路径。"
						,array($sExtPath),$e) ;
			}
			$this->setInstalledExtension($aExtension) ;
		}
		
		// 取得Settnig中的另一项item数据：激活使用的扩展名称数组
		$this->arrEnableExtensionNames = $aSetting->item("/extensions",'enable') ?: array() ;
	}
	
	/**
	 * @return ExtensionMetainfo
	 */
	public function extensionMetainfo($sName)
	{
		return isset($this->arrInstalledExtensions[$sName])? $this->arrInstalledExtensions[$sName]: null ;
	}

	/**
	 * \Iterator
	 */
	public function metainfoIterator()
	{
		return new \ArrayIterator($this->arrInstalledExtensions) ;
	}

	/**
	 * \Iterator
	 */
	public function enableExtensionMetainfoIterator()
	{
		$arrExtMetas = array() ;
		foreach($this->extensionPriorities() as $nPriority)
		{
			foreach($this->enableExtensionNameIterator($nPriority) as $sExtName)
			{
				if( !$aMetainfo = $this->extensionMetainfo($sExtName) )
				{
					throw new Exception("遇到未知的扩展: %s",$sExtName) ;
				}
				$arrExtMetas[] = $aMetainfo ;
			}
		}
		return new \ArrayIterator($arrExtMetas) ;
	}
	
	/**
	 * \Iterator
	 */
	public function extensionPriorities()
	{
		return array_keys($this->arrEnableExtensionNames) ;
	}
	
	/**
	 * \Iterator
	 */
	public function enableExtensionNameIterator($nPriority=-1)
	{
		if($nPriority<0)
		{
			return empty($this->arrEnableExtensionNames)?
					new \EmptyIterator():
					new \ArrayIterator(
						call_user_func_array('array_merge',$this->arrEnableExtensionNames)
					) ;
		}
		else 
		{
			return isset($this->arrEnableExtensionNames[$nPriority])?
						new \ArrayIterator($this->arrEnableExtensionNames[$nPriority]) :
						new \EmptyIterator() ;
		}
	}
	
	
	/**
	 * \Iterator
	 * @notice 这个函数返回的并不是已经启用的扩展的列表，而是扩展对象缓存列表。
	 */
	public function iterator()
	{
		return new \ArrayIterator($this->arrExtensionInstances) ;
	}
	
	/**
	 * @return Extension
	 */
	public function extension($sName) 
	{
		if( !isset($this->arrExtensionInstances[$sName]) )
		{
			if( !$aExtMeta = $this->extensionMetainfo($sName) )
			{
				return null ;
			}
			$sClass = $aExtMeta->className() ;
			if(!class_exists($sClass))
			{
				throw new ExtensionException("找不到扩展 %s 指定的扩展类: %s",array($sName,$sClass)) ;
			}
			$aExtension = new $sClass($aExtMeta) ;
			$this->add($aExtension) ; 
		}
		return $this->arrExtensionInstances[$sName] ;
	}
	
	public function add(Extension $aExt)
	{
		$this->arrExtensionInstances[$aExt->metainfo()->name()] = $aExt ;
	}
	
	public function registerPackageNamespace($sNamespace,$sExtName)
	{
		$this->arrExtensionPackages[$sNamespace] = $sExtName ;
	}
	
	public function extensionNameByClass($sClass)
	{
		$nClassLen = strlen($sClass) ;

		for(end($this->arrExtensionPackages);$sNamespace=key($this->arrExtensionPackages);prev($this->arrExtensionPackages))
		{
			$nNamespaceLen = strlen($sNamespace) ;
			if( $nClassLen>$nNamespaceLen and substr($sClass,0,$nNamespaceLen)==$sNamespace and substr($sClass,$nNamespaceLen,1)=='\\' )
			{
				return current($this->arrExtensionPackages) ;
			}
		}
	}
	
	public function extensionNameByNamespace($sNs)
	{
		$nNsLen = strlen($sNs) ;
			
		for(end($this->arrExtensionPackages);$sNamespace=key($this->arrExtensionPackages);prev($this->arrExtensionPackages))
		{
			$nNamespaceLen = strlen($sNamespace) ;
			if( $nNsLen >= $nNamespaceLen 
				and substr($sNs,0,$nNamespaceLen)==$sNamespace 
				and (
					substr($sNs,$nNamespaceLen,1)=='\\'
					or $nNsLen == $nNamespaceLen )
				)
			{
				return current($this->arrExtensionPackages) ;
			}
		}
	}
	
	public function setInstalledExtension(ExtensionMetainfo $aExtMetainfo)
	{
		$this->arrInstalledExtensions[$aExtMetainfo->name()] = $aExtMetainfo ;
	}
	
	/**
	 * @brief 加入enable扩展
	 * 
	 * 在激活扩展时，enable扩展列表会发生变化。
	 * 需要同时更新这个类的内容，
	 * 否则会发生数据不同步的问题。
	 * @seealso ExtensionSetup::enable()
	 */
	public function addEnableExtension(ExtensionMetainfo $aExtensionMetainfo){
		// arrEnableExtensionNames
		$nPriority = $aExtensionMetainfo->priority() ;
		$sName = $aExtensionMetainfo->name() ;
		if(!isset($this->arrEnableExtensionNames[$nPriority])){
			$this->arrEnableExtensionNames[$nPriority] = array();
		}
		$this->arrEnableExtensionNames[$nPriority] [] = $sName ;
		
		// arrExtensionInstances
		$aExtension = new Extension($aExtensionMetainfo);
		$this->arrExtensionInstances[$sName] = $aExtension ;
	}
	
	/**
	 * @brief 移除enable扩展
	 * 
	 * 在禁用扩展时，enable扩展列表会发生变化。
	 * 需要同时更新这个类的内容，
	 * 否则会发生数据不同步的问题。
	 * @seealso ExtensionSetup::disable()
	 */
	public function removeEnableExtension(ExtensionMetainfo $aExtensionMetainfo){
		// arrEnableExtensionNames
		foreach($this->arrEnableExtensionNames as &$arrExtensionNameList){
			$sExtName = $aExtensionMetainfo->name() ;
			$arrExtensionNameList = array_diff( $arrExtensionNameList , array( $sExtName ) );
		}
		
		// arrExtensionInstances
		// unset($this->arrExtensionInstances[$aExtensionMetainfo->name()]);
	}
	
	/**
	 * @brief 移除installed扩展
	 * 
	 * 在卸载扩展时，installed扩展列表会发生变化。
	 * 需要同时更新这个类的内容，
	 * 否则会发生数据不同步的问题。
	 * @seealso ExtensionSetup::uninstall()
	 */
	public function removeInstallExtension(ExtensionMetainfo $aExtensionMetainfo){
		// arrInstalledExtensions
		$sName = $aExtensionMetainfo->name() ;
		unset($this->arrInstalledExtensions[$sName]);
	}
	
	private $arrEnableExtensionNames = array() ;
	
	private $arrInstalledExtensions = array() ;
		
	private $arrExtensionInstances = array() ;
	
	private $arrExtensionPackages = array() ;
}




