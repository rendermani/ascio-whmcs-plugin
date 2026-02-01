class AscioImporter {
    getCertIds () {
        var certIds=[];
        $(".cert-select").each(function(nr,checkbox) {
            var cb = $(checkbox);
            if(checkbox.checked) {
                certIds.push($(checkbox).data("id"));
            }
            
        });
        return certIds;

    }
    calulateSsl () {
        var certIds=this.getCertIds();
        $.ajax({
            url: "../modules/addons/asciotools/ssl/import.php?action=preview",
            datatype : "json",
            data: { 
                margin: $("#margin").val(),
                round: $("#round").val(),
                products: certIds 
            }
          }).done(function(data) {            
            $("#preview").html(data.html)
          });          
    } 
    importSsl() {
        var certIds=this.getCertIds();
        $.ajax({
            url: "../modules/addons/asciotools/ssl/import.php?action=import",
            datatype : "json",
            data: { 
                products: certIds,
            }
          }).done(function(data) {            
            var d = new Date();
            console.log(certIds.length);
            $("#preview").html('<div class="alert alert-success" role="alert">['+d.toLocaleString()+'] <b>'+certIds.length+' Products imported!</b></div>' + data.html)
          }); 
    }
}
class AscioInstaller {        
    update(nr) {
        var self = this;
        if(!nr) nr = 0;           
        var element = $(".update-action")[nr]
        if(element) {
            element = $(element);
            var action = element.data("action");
            var icon = $("#icon-"+action);
            icon.removeClass("glyphicon-remove");
            icon.addClass("glyphicon-time");
            icon.attr("style","color:black");    
            $.ajax({
                url: "../modules/addons/asciotools/ssl/Installer/install.php",
                datatype : "json",
                data: { 
                    "action": element.data("action"),
                    "local-path" : element.data("local-path"),
                    "git": element.data("git"),
                    "module": element.data("module"),
                }              
              }).done(function(data) {            
                if(data.error) {
                    element.html('<div style="color:darkred" role="alert">'+data.error+'</div>');
                    icon.addClass("glyphicon-remove");
                    icon.removeClass("glyphicon-time");
                    icon.attr("style","color:darkred");                    
                } else {
                    element.html('<div style="color:darkgreen" role="alert">Successfully updated.</div>');
                    icon.addClass("glyphicon-ok");
                    icon.removeClass("glyphicon-remove");
                    icon.removeClass("glyphicon-time");
                    icon.attr("style","color:darkgreen");
                    $("#text-"+action).attr("style","color:darkgreen");
                    self.update(nr+1);
                }
                
              }).fail(function() {
                alert( "error" );
              }); 

        } else {
            $(".ascio-tools-links").show();
        }
        
    }
}
class AscioSettings {
    validate () {
        var self  = this;
        $("#result").html("<br/><br/>");
        this.validateAccount("live","domain");
        this.validateAccount("testing","domain");
        this.validateAccount("testing","dns");             
    }
    validateAccount (environment,type) {
        var self = this; 
        this.setIcon(environment,type,"time");
        return $.ajax({
            url: "../modules/addons/asciotools/validate-settings.php?environment="+environment+"&type="+type,
            datatype : "json",
            method : "post",
            data:  $("#settingsform").serialize()        
          }).done(function(data) {
            var icon = data.error ? "remove" : "ok";
            self.setIcon(environment,type,icon);
            $("#result").html( $("#result").html() + data.message);        
        })
    }
    setIcon(environment,type,icon) {
        var color = "gray";
        switch(icon) {
            case "ok": color = "darkgreen"; break;
            case "remove": color = "darkred"; break;
            default: break;
        }
        if(type=="dns") {
            var dns = $("#progress-live-dns");
            dns.attr("class","glyphicon glyphicon-"+icon);
            dns.attr("style","color:"+color);
        } else {
            var account = $("#progress-"+environment+"-"+type+"-account");
            account.attr("class","glyphicon glyphicon-"+icon);
            account.attr("style","color:"+color);
            var password = $("#progress-"+environment+"-"+type+"-password");
            password.attr("class","glyphicon glyphicon-"+icon);
            password.attr("style","color:"+color);
            
        }
    }
}
jQuery(document).ready(function(){
    var ascioImporter = new AscioImporter();
    $("#calculate").click(function() {
        ascioImporter.calulateSsl()
    });
    $("#upload").click(function() {
        ascioImporter.importSsl();        
    });
    $("#update").click(function() {
        ascioInstaller = new AscioInstaller();
        ascioInstaller.update();
    });
    $("#validate").click(function() {
        ascioSettings = new AscioSettings();
        ascioSettings.validate();
    });
})