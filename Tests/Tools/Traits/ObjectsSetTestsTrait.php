<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2018 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tests\Tools\Traits;

use ArrayObject;
use Splash\Client\Splash;

/**
 * @abstract    Splash Test Tools - Objects Fields Management
 *
 * @author SplashSync <contact@splashsync.com>
 */
trait ObjectsSetTestsTrait
{
    /** @var array */
    protected $fields           = array();

    /**
     * @var string      Md5 CheckSum of Current Field Data Block
     */
    protected $fieldMd5;
    
    /**
     * @abstract    Execute Single Field Test From Service
     * @param       string      $objectType         Splash Object Type Name
     * @param       ArrayObject $field              Current Tested Field (ArrayObject)
     * @param       string      $forceObjectId      Object Id (Update) or Null (Create)
     * @return      bool
     */
    public function coreTestSetSingleFieldFromService($objectType, $field, $forceObjectId = null)
    {
        //====================================================================//
        //   OBJECT CREATE TEST
        //====================================================================//
        
        //====================================================================//
        //   Generate Dummy Object Data (Required Fields Only)
        $newData = $this->prepareForTesting($objectType, $field);
        if (false == $newData) {
            return true;
        }
        
        //====================================================================//
        //   Execute Create Test
        $objectId = $this->setObjectFromService($objectType, $newData, $forceObjectId);
        
        //====================================================================//
        // BOOT or REBOOT MODULE
        $this->setUp();
        
        //====================================================================//
        //   OBJECT UPDATE TEST
        //====================================================================//
        
        //====================================================================//
        //   Generate Dummy Object Data (Required Fields Only)
        $updateData = $this->prepareForTesting($objectType, $field);
        $this->assertNotEmpty($updateData);
        if (false == $updateData) {
            return true;
        }

        //====================================================================//
        //   Execute Update Test
        $this->setObjectFromService($objectType, $updateData, $objectId);
        
        //====================================================================//
        //   OBJECT DELETE
        //====================================================================//
        
        //====================================================================//
        // If Test was Forced on a Specific Object (Local Sequences)
        if (!is_null($forceObjectId)) {
            return true;
        }
        
        //====================================================================//
        //   Delete Object From Module
        $this->deleteObjectFromModule($objectType, $objectId);
    }
        
    /**
     * @abstract    Ensure Set/Write Test is Possible & Generate Fake Object Data
     *              -> This Function uses Preloaded Fields
     *              -> If Md5 provided, check Current Field was Modified
     *
     * @param       string      $objectType     Current Object Type
     * @param       ArrayObject $field          Current Tested Field (ArrayObject)
     * @param       bool        $unik           Ask for Unik Field Data
     *
     * @return      array|false      Generated Data Block or False if not Allowed
     */
    public function prepareForTesting($objectType, $field, $unik = true)
    {
        //====================================================================//
        //   Verify Test is Required
        if (!$this->verifyTestIsAllowed($objectType, $field)) {
            return false;
        }
        //====================================================================//
        // Return Generated Object Data
        return $this->generateObjectData($objectType, $field, $unik);
    }
    
    //==============================================================================
    //      DATA VERIFICATION FUNCTIONS
    //==============================================================================
    
    public function verifySetResponse($objectType, $objectId, $action, $expectedData)
    {
        //====================================================================//
        //   Verify Object Id Is Not Empty
        $this->assertNotEmpty($objectId, "Returned New Object Id is Empty");

        //====================================================================//
        //   Add Object Id to Created List
        $this->addTestedObject($objectType, $objectId);
        
        //====================================================================//
        //   Verify Object Id Is in Right Format
        $this->assertTrue(
            is_integer($objectId) || is_string($objectId),
            "New Object Id is not an Integer or a Strings"
        );
        
        //====================================================================//
        //   Verify Object Change Was Commited
        $this->assertIsFirstCommited($action, $objectType, (string) $objectId);
        
        //====================================================================//
        //   Read Object Data
        $currentData    =   Splash::object($objectType)
            ->get((string) $objectId, $this->reduceFieldList($this->fields));
        $this->assertInternalType('array', $currentData);
        //====================================================================//
        //   Verify Object Data are Ok
        $this->compareDataBlocks($this->fields, $expectedData, $currentData, $objectType);
    }
    
    public function verifyDeleteResponse($objectType, $objectId, $data)
    {
        //====================================================================//
        //   Verify Response
        $this->assertIsSplashBool($data, "Object Delete Response Must be a Bool");
        $this->assertNotEmpty($data, "Object Delete Response is Not True");
        
        //====================================================================//
        // Lock New Objects To Avoid Action Commit
        Splash::object($objectType)->lock($objectId);
        
        //====================================================================//
        //   Verify Repeating Delete as Same Result
        $repeatedResponse    =   Splash::object($objectType)->delete($objectId);
        $this->assertTrue(
            $repeatedResponse,
            "Object Repeated Delete, Must return True even if Object Already Deleted."
        );
        
        //====================================================================//
        //   Verify Object not Present anymore
        $fields         =   $this->reduceFieldList(Splash::object($objectType)->fields(), true, false);
        $getResponse    =   Splash::object($objectType)->get($objectId, $fields);
        $this->assertFalse($getResponse, "Object Not Delete, I can still read it!!");
    }
    
    //==============================================================================
    //      COMPLETE TESTS EXECUTION FUNCTIONS
    //==============================================================================
    
    /**
     * @abstract    Execute Single Field Test From Module
     * @param       string      $objectType         Splash Object Type Name
     * @param       ArrayObject $field              Current Tested Field (ArrayObject)
     * @param       string      $forceObjectId      Object Id (Update) or Null (Create)
     * @return      bool
     */
    protected function coreTestSetSingleFieldFromModule($objectType, $field, $forceObjectId = null)
    {
        //====================================================================//
        //   OBJECT CREATE TEST
        //====================================================================//
        
        //====================================================================//
        //   Generate Dummy Object Data (Required Fields Only)
        $newData = $this->prepareForTesting($objectType, $field);
        if (false == $newData) {
            return true;
        }
        
        //====================================================================//
        //   Execute Create Test
        $objectId = $this->setObjectFromModule($objectType, $newData, $forceObjectId);
                
        //====================================================================//
        //   OBJECT UPDATE TEST
        //====================================================================//
        
        //====================================================================//
        //   Update Data Focused Field Data
        $updateData = $this->prepareForTesting($objectType, $field);
        $this->assertNotEmpty($updateData);
        if (false == $updateData) {
            return true;
        }

        //====================================================================//
        //   Execute Update Test
        $this->setObjectFromModule($objectType, $updateData, $objectId);
        
        //====================================================================//
        //   OBJECT DELETE
        //====================================================================//
        
        //====================================================================//
        // If Test was Forced on a Specific Object (Local Sequences)
        if (!is_null($forceObjectId)) {
            return true;
        }
        
        //====================================================================//
        //   Delete Object From Module
        $this->deleteObjectFromModule($objectType, $objectId);
    }
    
    //==============================================================================
    //      UNIT TESTS EXECUTION FUNCTIONS
    //==============================================================================
    
    /**
     * @abstract    Execute Object Create or Update Test with Given Data (From Module)
     * @param       string      $objectType         Splash Object Type Name
     * @param       array       $objectData         Splash Data Block
     * @param       string      $forceObjectId      Object Id (Update) or Null (Create)
     */
    protected function setObjectFromModule($objectType, $objectData, $forceObjectId = null)
    {
        //====================================================================//
        // Lock New Objects To Avoid Action Commit
        Splash::object($objectType)->lock($forceObjectId);
        //====================================================================//
        // Clean Objects Commited Array
        Splash::$commited = array();
        //====================================================================//
        //   Update Object on Module
        $objectId = Splash::object($objectType)->set($forceObjectId, $objectData);
        //====================================================================//
        //   Verify Response
        $this->verifySetResponse($objectType, $objectId, ($forceObjectId ? SPL_A_UPDATE : SPL_A_CREATE), $objectData);
        //====================================================================//
        // UnLock New Objects To Avoid Action Commit
        Splash::object($objectType)->unLock();
        //====================================================================//
        // Lock This Object To Avoid Being Selected for Linking
        $this->setCurrentObject($objectType, (string) $objectId);
        //====================================================================//
        // Retun Object Id
        return $objectId;
    }
    
    /**
     * @abstract    Execute Object Create or Update Test with Given Data (From Service)
     * @param       string      $objectType         Splash Object Type Name
     * @param       array       $objectData         Splash Data Block
     * @param       string      $forceObjectId      Object Id (Update) or Null (Create)
     */
    protected function setObjectFromService($objectType, $objectData, $forceObjectId = null)
    {
        //====================================================================//
        // Clean Objects Commited Array
        Splash::$commited = array();
        //====================================================================//
        //   Create a New Object via Service
        $objectId = $this->genericAction(
            SPL_S_OBJECTS,
            SPL_F_SET,
            __METHOD__,
            array( "id" => $forceObjectId, "type" => $objectType, "fields" => $objectData)
        );
        //====================================================================//
        //   Verify Response
        $this->verifySetResponse($objectType, $objectId, ($forceObjectId ? SPL_A_UPDATE : SPL_A_CREATE), $objectData);
        //====================================================================//
        // UnLock New Objects To Avoid Action Commit
        Splash::object($objectType)->unLock();
        //====================================================================//
        // Lock This Object To Avoid Being Selected for Linking
        $this->setCurrentObject($objectType, $objectId);
        //====================================================================//
        // Retun Object Id
        return $objectId;
    }
    
    /**
     * @abstract    Execute Object Delete Test (From Module)
     * @param       string      $objectType         Splash Object Type Name
     * @param       string      $objectId           Object Id
     */
    protected function deleteObjectFromModule($objectType, $objectId)
    {
        //====================================================================//
        // Lock New Objects To Avoid Action Commit
        Splash::object($objectType)->lock($objectId);
        //====================================================================//
        //   Delete Object on Module
        $data = Splash::object($objectType)->delete($objectId);
        //====================================================================//
        //   Verify Response
        $this->verifyDeleteResponse($objectType, $objectId, $data);
    }
    
    //==============================================================================
    //      TESTS PREPARATION FUNCTIONS
    //==============================================================================

    protected function verifyTestIsAllowed($objectType, $field = null)
    {
        $definition = Splash::object($objectType)->description();

        $this->assertNotEmpty($definition);
        //====================================================================//
        //   Verify Create is Allowed
        if (!$definition["allow_push_created"]) {
            return false;
        }
        //====================================================================//
        //   Verify Update is Allowed
        if (!$definition["allow_push_updated"]) {
            return false;
        }
        //====================================================================//
        //   Verify Delete is Allowed
        if (!$definition["allow_push_deleted"]) {
            return false;
        }
        //====================================================================//
        //   Verify Field is To Be Tested
        if (!is_null($field) && $field->notest) {
            return false;
        }
        
        return true;
    }

    /**
     * @abstract    Generate Fake Object Data
     *              -> This Function uses Preloaded Fields
     *              -> If Md5 provided, check Current Field was Modified
     *
     * @param       string      $objectType     Current Object Type
     * @param       ArrayObject $field          Current Tested Field (ArrayObject)
     * @param       bool        $unik           Ask for Unik Field Data
     *
     * @return      array|false      Generated Data Block or False if not Allowed
     */
    protected function generateObjectData($objectType, $field, $unik = true)
    {
        //====================================================================//
        // Generate Required Fields List
        $this->fields   =   $this->fakeFieldsList($objectType, array($field->id), true);
        
        //====================================================================//
        // Prepare Fake Object Data
        //====================================================================//
        $try = 0;
        do {
            //====================================================================//
            // Generate Object Data
            $fakeData       =   $this->fakeObjectData($this->fields);
            if (false == $fakeData) {
                return false;
            }
            //====================================================================//
            // Check if Compare is Required
            if ((false == $unik) || (empty($this->fieldMd5))) {
                //====================================================================//
                // Store MD5 of New Generated Field Data
                $this->fieldMd5 = $this->getFakeDataMd5($fakeData, $field);

                return $fakeData;
            }
            
            $fakeDataMd5 = $this->getFakeDataMd5($fakeData, $field);

            //====================================================================//
            //   Ensure Field Data was modified
            $try++;
        } while (($this->fieldMd5 === $fakeDataMd5) && ($try < 5));
        
        //====================================================================//
        // Store MD5 of New Generated Field Data
        $this->fieldMd5 = $this->getFakeDataMd5($fakeData, $field);

        //====================================================================//
        // Return Generated Object Data
        return $fakeData;
    }
    
    /**
     * @abstract    Generate Object Data Md5 Checksum to Ensure Data are different
     *
     * @param       array       $fakeData       Faker Object Data Set
     * @param       ArrayObject $field          Current Tested Field (ArrayObject)
     *
     * @return      string                      Md5 CheckSum
     */
    protected function getFakeDataMd5($fakeData, $field)
    {
        //====================================================================//
        // Filter data to focus on Tested Field
        $filteredData   =   $this->filterData($fakeData, array($field->id));
        //====================================================================//
        // Data Block is Empty(i.e: ReadOnly Field)
        if (empty($filteredData)) {
            return md5(serialize($fakeData));
        }

        return md5(serialize($filteredData));
    }
}
