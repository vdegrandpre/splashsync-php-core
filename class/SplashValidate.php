<?php
/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @abstract    Tooling Class for Validation of Splash Php Module Contents 
 * @author      B. Paquier <contact@splashsync.com>
 */

//====================================================================//
//  CLASS DEFINITION
//====================================================================//

class SplashValidate
{

//====================================================================//
// *******************************************************************//
//  VALIDATE LOCAL CORE CLASS & FUNCTIONS
// *******************************************************************//
//====================================================================//

   /**
    *   @abstract   Verify Local Core Class Exists & Is Valid 
    *  
    *   @return     int         $result     0 if KO, 1 if OK
    */
    public function isValidLocalClass() 
    {   
        $ClassName = SPLASH_CLASS_PREFIX . "Local";
        //====================================================================//
        // Verify Results in Cache
        if ( isset($this->ValidLocalClass[$ClassName]) ) {
            return $this->ValidLocalClass[$ClassName];
        }
        
        $this->ValidLocalClass[$ClassName] = False;

        //====================================================================//
        // Verify Local Path Exist
        if (  $this->isValidLocalPath() == false ) {
            return False;
        }   

        //====================================================================//
        // Verify Local Class File Exist
        $path    =   dirname(dirname(__FILE__)) . SPLASH_LOCALPATH;
        $filename = $path . "/Core/Local.php";
        if ( !file_exists($filename) ) {
            return Splash::Log()->Err(Splash::Trans("ErrLocalClass",$filename));                
        }

        //====================================================================//
        // Include File
        include_once($filename);

        //====================================================================//
        // Verify Splash Local Core Class Exists
        if (class_exists( $ClassName ) == False) {
            return Splash::Log()->Err(Splash::Trans("ErrLocalClass",$ClassName));                
        }
        
        //====================================================================//
        // Verify Splash Local Core Functions Exists
        if ($this->isValidLocalFunction( "Parameters" ,  $ClassName ) == False) {
            return False;                
        }

        if ($this->isValidLocalFunction( "Includes" ,  $ClassName ) == False) {
            return False;                
        }

        if ($this->isValidLocalFunction( "Informations" ,  $ClassName ) == False) {
            return False;                
        }
        
        if ($this->isValidLocalFunction( "SelfTest" ,  $ClassName ) == False) {
            return False;                
        }
        
        $this->ValidLocalClass[$ClassName] = True;
        
        return $this->ValidLocalClass[$ClassName];
    } 
    
    /**
    *   @abstract   Verify Local Core Parameters are Valid 
    *  
    *   @return     int         $result     0 if KO, 1 if OK
    */
    public function isValidLocalParameterArray($In) 
    {   
        //====================================================================//
        // Verify Array Given
        if ( !is_array( $In ) ) {
            return Splash::Log()->Err( Splash::Trans("ErrorCfgNotAnArray",  get_class($In) ) );
        }
        
        //====================================================================//
        // Required Parameters are Available
        //====================================================================//
        if ( !array_key_exists( "WsIdentifier" , $In ) ) {
            return Splash::Log()->Err( Splash::Trans("ErrorCfgKeyMissing",  "WsIdentifier" ) );
        }

        if ( !array_key_exists( "WsEncryptionKey" , $In ) ) {
            return Splash::Log()->Err( Splash::Trans("ErrorCfgKeyMissing",  "WsEncryptionKey" ) );
        }
        
        return True;
        
    } 

//====================================================================//
// *******************************************************************//
//  VALIDATE OBJECTS CLASSES & FUNCTIONS
// *******************************************************************//
//====================================================================//
    
   /**
    *   @abstract   Verify this parameter is a valid object type name 
    *   @param      string      $ObjectType     Object Class/Type Name  
    *   @return     bool
    */
    public function isValidObject($ObjectType)
    {
        //====================================================================//
        // Verify Result in Cache
        if ( isset($this->ValidLocalObject[$ObjectType]) ) {
            return $this->ValidLocalObject[$ObjectType];
        }        
        
        $this->ValidLocalObject[$ObjectType] = False;
        
        //====================================================================//
        // Verify Object File Exist & is Valid
        if ( !$this->isValidObjectFile($ObjectType) ) {
            return False;
        }
        
        //====================================================================//
        // Verify Object Class Exist & is Valid
        if ( !$this->isValidObjectClass($ObjectType) ) {
            return False;
        }
        
        $this->ValidLocalObject[$ObjectType] = True;
        return True;
    } 

    /**
     *      @abstract     Verify a Local Object File is Valid. 
     *      @param        string    $ObjectType     Object Type Name 
     *      @return       int                       0 if KO, 1 if OK
     */
    private function isValidObjectFile($ObjectType)
    {
        //====================================================================//
        // Verify Local Path Exist
        if (  $this->isValidLocalPath() == false ) {
            return False;
        }         
        
        //====================================================================//
        // Verify Object File Exist
        $filename = Splash::Configuration()->localpath . "/Objects/" . $ObjectType . ".php";
        if (file_exists($filename) == FALSE) {
            $msg = "Local Object File Not Found.</br>";
            $msg.= "Current Filename : " . $filename . "";
            return Splash::Log()->Err($msg);
        } 
        
        //====================================================================//
        // Include File
        include_once($filename);
        
        return True;
    }        
    
    /**
     *      @abstract     Verify Availability of a Local Object Class. 
     *      @param        string    $ObjectType     Object Type Name 
     *      @return       int                       0 if KO, 1 if OK
     */
    private function isValidObjectClass($ObjectType)
    {
        $ClassName = SPLASH_CLASS_PREFIX . $ObjectType;

        //====================================================================//
        // Verify Splash Local Core Class Exists
        if (class_exists( $ClassName ) == False) {
            return Splash::Log()->Err(Splash::Trans("ErrLocalClass",$ObjectType));                
        }        
        
        //====================================================================//
        // Verify Local Object Core Class Functions Exists   
        //====================================================================//
        
        //====================================================================//
        // Read Object Disable Flag  
        if ($this->isValidLocalFunction("getIsDisabled",$ClassName) == FALSE) {
            $this->ValidLocalObject[$ObjectType] = false;
            return false;
        }        
        if ( $ClassName::getIsDisabled() ) {
            $this->ValidLocalObject[$ObjectType] = false;
            return false;
        }        
        
        //====================================================================//
        // Verify Local Object Class Functions Exists   
        //====================================================================//
        
        //====================================================================//
        // Read Object Available Fields List  
        if ($this->isValidLocalFunction(SPL_F_FIELDS,$ClassName) == FALSE) {
            $this->ValidLocalObject[$ObjectType] = false;
            return false;
        }
//        //====================================================================//
//        // Read Object Available Filterss List   
//        if ($this->isValidLocalFunction(SPL_F_FILTERS,$ClassName) == FALSE) {
//            $this->ValidLocalObject[$ObjectType] = false;
//            return false;
//        }
        //====================================================================//
        // Read Object List 
        if ($this->isValidLocalFunction(SPL_F_LIST,$ClassName) == FALSE) {
            $this->ValidLocalObject[$ObjectType] = false;
            return false;
        }
        //====================================================================//
        // Read Object Data 
        if ($this->isValidLocalFunction(SPL_F_GET,$ClassName) == FALSE) {
            $this->ValidLocalObject[$ObjectType] = false;
            return false;
        }
        //====================================================================//
        // Write Object Data 
        if ($this->isValidLocalFunction(SPL_F_SET,$ClassName) == FALSE) {
            $this->ValidLocalObject[$ObjectType] = false;
            return false;
        }
        //====================================================================//
        // Delete Object Data 
        if ($this->isValidLocalFunction(SPL_F_DEL,$ClassName) == FALSE) {
            $this->ValidLocalObject[$ObjectType] = false;
            return false;
        }

        return True;
    }   
        
   /**
    *   @abstract   Verify this Object Type is valid in Local Syetem 
    *   @param      string      $ObjectType         Object Type Name String     
    *   @return     bool
    */
    public static function isValidObjectType($ObjectType) 
    {        
        //====================================================================//
        // Verify Type Name is in List
        return in_array($ObjectType, Splash::Objects());
    } 

//====================================================================//
// *******************************************************************//
//  VALIDATE OBJECTS I/O
// *******************************************************************//
//====================================================================//

    /**
     *  @abstract   Verify Object Identifier
     *  
     *  @param      string      $Id     Object Identifier
     * 
     *  @return     bool
     */
    public function isValidObjectId($Id) 
    {        
        //====================================================================//
        // Checks Id is not Null
        if ( is_null($Id) ) {
            return Splash::Log()->Err("ErrEmptyObjectId");
        }

        //====================================================================//
        // Checks Id is String or Int
        if ( !is_string($Id) && !is_numeric($Id) ) {
            return Splash::Log()->Err("ErrWrongObjectId");
        }
        
        //====================================================================//
        // Checks List Not Empty
        if ( is_numeric($Id) && ( $Id < 0 ) ) {
            return Splash::Log()->Err("ErrNegObjectId");
        }
        
        return Splash::Log()->Deb("MsgObjectIdOk");
    }     
    
    /**
     *  @abstract   Verify Object Field List  
     *  
     *  @param      array   $List       Object Field List
     * 
     *  @return     bool
     */
    public function isValidObjectFieldsList($List) 
    {        
        //====================================================================//
        // Checks List Type
        if ( !is_array($List) && !is_a($List,"ArrayObject") ) {
//            Splash::Log()->www("Fields List", $List);
            return Splash::Log()->Err("ErrWrongFieldList");
        }
        
        //====================================================================//
        // Checks List Not Empty
        if (empty($List)) {
            return Splash::Log()->Err("ErrEmptyFieldList");
        }
        
        return Splash::Log()->Deb("MsgFieldListOk");
    }     
    
//====================================================================//
// *******************************************************************//
//  VALIDATE WIDGETS CLASSES & FUNCTIONS
// *******************************************************************//
//====================================================================//
    
   /**
    *   @abstract   Verify this parameter is a valid widget type name 
    *   @param      string      $WidgetType     Widget Class/Type Name  
    *   @return     bool
    */
    public function isValidWidget($WidgetType)
    {
        //====================================================================//
        // Verify Result in Cache
        if ( isset($this->ValidLocalWidget[$WidgetType]) ) {
            return $this->ValidLocalWidget[$WidgetType];
        }        
        $this->ValidLocalWidget[$WidgetType] = False;
        //====================================================================//
        // Verify Widget File Exist & is Valid
        if ( !$this->isValidWidgetFile($WidgetType) ) {
            return False;
        }
        //====================================================================//
        // Verify Widget Class Exist & is Valid
        if ( !$this->isValidWidgetClass($WidgetType) ) {
            return False;
        }
        $this->ValidLocalWidget[$WidgetType] = True;
        return True;
    } 
    
    /**
     *      @abstract     Verify a Local Widget File is Valid. 
     *      @param        string    $WidgetType     Widget Type Name 
     *      @return       int                       0 if KO, 1 if OK
     */
    private function isValidWidgetFile($WidgetType)
    {
        //====================================================================//
        // Verify Local Path Exist
        if (  $this->isValidLocalPath() == false ) {
            return False;
        }         
        //====================================================================//
        // Verify Object File Exist
        $filename = Splash::Configuration()->localpath . "/Widgets/" . $WidgetType . ".php";
        if (file_exists($filename) == FALSE) {
            $msg = "Local Widget File Not Found.</br>";
            $msg.= "Current Filename : " . $filename . "";
            return Splash::Log()->Err($msg);
        } 
        //====================================================================//
        // Include File
        include_once($filename);
        
        return True;
    }    
    
    /**
     *      @abstract     Verify Availability of a Local Widget Class. 
     *      @param        string    $WidgetType     Widget Type Name 
     *      @return       int                       0 if KO, 1 if OK
     */
    private function isValidWidgetClass($WidgetType)
    {
        $ClassName = SPLASH_CLASS_PREFIX . $WidgetType;

        //====================================================================//
        // Verify Splash Local Core Class Exists
        if (class_exists( $ClassName ) == False) {
            return Splash::Log()->Err(Splash::Trans("ErrLocalClass",$WidgetType));                
        }        
        
        //====================================================================//
        // Verify Local Widget Core Class Functions Exists   
        //====================================================================//
        
        //====================================================================//
        // Read Object Disable Flag  
        if ($this->isValidLocalFunction("getIsDisabled",$ClassName) == FALSE) {
            $this->ValidLocalWidget[$WidgetType] = false;
            return false;
        }        
        if ( $ClassName::getIsDisabled() ) {
            $this->ValidLocalWidget[$WidgetType] = false;
            return false;
        }        
        
        //====================================================================//
        // Verify Local Widget Class Functions Exists   
        //====================================================================//
        
        //====================================================================//
        // Read Object Available Fields List  
        if ($this->isValidLocalFunction(SPL_F_WIDGET_DEFINITION,$ClassName) == FALSE) {
            $this->ValidLocalWidget[$WidgetType] = false;
            return false;
        }
        //====================================================================//
        // Read Object List 
        if ($this->isValidLocalFunction(SPL_F_WIDGET_GET,$ClassName) == FALSE) {
            $this->ValidLocalWidget[$WidgetType] = false;
            return false;
        }

        return True;
    }     
//====================================================================//
// *******************************************************************//
//  VALIDATE COMMONS FUNCTIONS
// *******************************************************************//
//====================================================================//
    
   /**
    *   @abstract   Verify Local Path Exists  
    *   @return     int         $result     0 if KO, 1 if OK
    */
    public function isValidLocalPath() 
    {        
        //====================================================================//
        // Verify no result in Cache
        if ( !isset($this->ValidLocalPath) ) {
            $path    =   dirname(dirname(__FILE__)) . SPLASH_LOCALPATH;
            //====================================================================//
            // Verify Local Path Exist
            if ( !is_dir($path)) {
                $this->ValidLocalPath = False;
                return Splash::Log()->Err(Splash::Trans("ErrLocalPath",$path));
            }     
            $this->ValidLocalPath = true;
            
        }
        return $this->ValidLocalPath;
    }        
    
    /**
     *      @abstract     Verify Availability of a local method/function prior to task execution. 
     *      @param        string    $Method         Function Name 
     *      @param        string    $ClassName     Optionnal Class Name 
     *      @return       int                       0 if KO, 1 if OK
     */
    public function isValidLocalFunction($Method,$ClassName)
    {
        //====================================================================//
        // Verify Result in Cache
        if ( isset($this->ValidLocalFunctions[$ClassName][$Method]) ) {
            return $this->ValidLocalFunctions[$ClassName][$Method];
        }
        
        //====================================================================//
        // Verify Class Method Exists
        if (method_exists($ClassName, $Method) == FALSE) {
            $this->ValidLocalFunctions[$ClassName][$Method] = False;
            return Splash::Log()->Err( Splash::Trans("ErrLocalFunction",$ClassName,$Method) );
        }
        $this->ValidLocalFunctions[$ClassName][$Method] = True;

        return $this->ValidLocalFunctions[$ClassName][$Method];
    }    

}
?>