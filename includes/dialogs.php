   <!-- Modal dialogs -->
 <!-- Add folder dialog -->
  <div class="modal" id="addFolderDialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header coloredHeader">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">Add Folder</h4>
        </div>
        <div class="modal-body">
          <div class='text-danger'></div>
          <label for="fn">Folder Name:</label> 
          <input id="fn" type="text" class="folderName form-control" value="" placeholder="NewFolderName" />
          
        </div>
        <div class="modal-footer">
            <div class="returnVal hidden">cancel</div>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" onclick="createFolder();" class="btn btn-primary">Add</button>
        </div>
      </div>
    </div>
  </div>
    <!-- general confirmation dialog -->
    <div id="confirmDialog" class="modal">
      <div class="modal-dialog">
        <div class="modal-content">
         <div class="modal-header coloredHeader">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title"></h4>
        </div>
          <div class="modal-body">
            <div class="confirmMsg"></div>
          </div>
          <div class="modal-footer">
            <div class="returnVal hidden">No</div>
            <button type="button" class="btn btn-default" data-dismiss="modal" >No</button>
            <button type="button" onclick="$(this).parent().find('.returnVal').text('yes');" class="btn btn-primary" data-dismiss="modal">Yes</button>
          </div>
        </div> <!-- modal-content-->
      </div> <!-- modal-dialog -->
    </div> <!-- modal -->

