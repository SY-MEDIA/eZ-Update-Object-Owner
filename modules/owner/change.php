<?php

include_once( 'lib/ezutils/classes/ezhttptool.php' );
$http =& eZHTTPTool::instance();

// $moduleName =& $Params['ModuleName'];
// $functionName =& $Params['FunctionName'];


// if browse was cancelled, redirect
if ( $Params['Module']->isCurrentAction( 'Cancel' ) )
{
    if ( $Params['Module']->hasActionParameter( 'CancelURI' ) )
    {
        return $Params['Module']->redirectTo( $Params['Module']->actionParameter( 'CancelURI' ) );
    }
    else
    {
        return $Params['Module']->redirectTo( $http->sessionVariable( 'LastAccessesURI' ) );
    }
}

if ( $Params['ObjectID'] )
{
    $object =& eZContentObject::fetch( $Params['ObjectID'] );
}

if ( !$Params['ObjectID'] or !$object )
{
    return $Params['Module']->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );
}

include_once( 'kernel/classes/ezcontentbrowse.php' );

if ( $Params['Module']->isCurrentAction( 'ChangeOwner' ) )
{
    $selectedObjectIDArray = eZContentBrowse::result( 'ChangeOwner' );

    if ( is_array( $selectedObjectIDArray ) and count( $selectedObjectIDArray ) > 0 )
    {
        $object->setAttribute( 'owner_id', $selectedObjectIDArray[0] );
        $object->store();

        // Clean up content cache
        include_once( 'kernel/classes/ezcontentcachemanager.php' );
        eZContentCacheManager::clearContentCache( $object->attribute( 'id' ) );
    }

    return $Params['Module']->redirectTo( $http->sessionVariable( 'LastAccessesURI' ) );
}
else
{
    $browseParams = array();
    $browseParams['action_name'] = 'ChangeOwner';
    $browseParams['from_page'] = '/owner/change/' . $Params['ObjectID'];
    $browseParams['description_template' ] = 'design:content/browse_owner.tpl';
    $browseParams['content'] = array( 'object_id' => $Params['ObjectID'] );

    $currentOwner =& $object->attribute( 'owner' );

    if ( $currentOwner )
    {
        $currentOwnerNodes = $currentOwner->attribute( 'assigned_nodes' );

        $ignoreNodeIDList = array();
        foreach ( $currentOwnerNodes as $currentOwnerNode )
        {
            $ignoreNodeIDList[] = $currentOwnerNode->attribute( 'node_id' );
        }

        $browseParams['ignore_nodes_select'] = $ignoreNodeIDList;
    }

    if ( $Params['StartNode'] )
    {
        $browseParams['start_node'] = $Params['StartNode'];
        $browseParams['from_page'] .= '/group/' . $Params['StartNode'];
    }
    $browseParams['cancel_page'] = $http->sessionVariable( 'LastAccessesURI' );
    return eZContentBrowse::browse( $browseParams, $Params['Module'] );
}

?>
