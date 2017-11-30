/**
 * Created by walter on 20.08.15.
 */
var files = [];

$(function(){
    if ($('.s_name').length) {
        $('form').liTranslit();
    }

    $(document).on('change', 'input[type=file]', prepareUpload);

    $(document).on('click', '.constructor-open-link', function () {
        var that = this;

        $("#constructorFieldModal").modal({
            show: false
        }).modal('show');

        $.ajax({
            url: $(that).attr('href'),
            type: 'POST',
            dataType: 'json',
            data: $(that).closest('form').serializeArray(),
            success: parseResponse,
            error: function (response) {
                alert(response.responseText);
            }
        });

        return false;
    });

    $(document).on('click', '.delete-video-link', function () {
        var that = this;

        $.ajax({
            url: $(that).attr('href'),
            type: 'GET',
            dataType: 'json',
            success: function () {
                $(that).parents('.form-group').remove();
                location.reload();
            },
            error: function (response) {
                alert(response.responseText);
            }
        });

        return false;
    });

    $("#constructorField .items").sortable({
        items: ' tr',
        stop: function (event, ui) {
            $.ajax({
                url: $("#constructorField").data('href'),
                type: 'POST',
                dataType: 'json',
                data: {items: $("#constructorField .items").sortable('toArray')}
            });
        }
    });

    $(document).on('click', '.delete-link', function () {
        var that = this;

        $.ajax({
            url: $(that).attr('href'),
            type: 'POST',
            dataType: 'json',
            data: $(that).closest('form').serializeArray(),
            success: parseResponse,
            error: function (response) {
                alert(response.responseText);
            }
        });

        return false;
    });

    $(document).on('click', '.crop-link', function () {
        var that = this;

        $.ajax({
            url: $(that).attr('href'),
            type: 'POST',
            dataType: 'json',
            data: $(that).closest('form').serializeArray(),
            success: parseResponse,
            error: function (response) {
                alert(response.responseText);
                return false;
            }
        });

        $("#productsImageCropperFieldModal").modal({
            show: false,
        }).modal('show');

        return false;
    });

    $('#productsImageCropperFieldModal').on('show.bs.modal', function (e) {
        setTimeout(function(){
            var $dataX = $("#dataX"),
                $dataY = $("#dataY"),
                $dataHeight = $("#dataHeight"),
                $dataWidth = $("#dataWidth");
            var ratio = false;
            if($('#data-h').length && $('#data-w').length) {
                var height = $('.crop-link').attr('data-h');
                var width = $('.crop-link').attr('data-w');
                ratio = width / height;
            }

            $(".img-container > img").cropper({
                aspectRatio: ratio,
                preview: ".img-preview",
                done: function(data) {
                    $dataX.val(Math.round(data.x));
                    $dataY.val(Math.round(data.y));
                    $dataHeight.val(Math.round(data.height));
                    $dataWidth.val(Math.round(data.width));
                }
            });
        }, 500);
    });

    $(document).on('click', '.save-cropped', function(){
        event.preventDefault();
        var that = this;
        var url = $(that).attr('href');
        var data = {
            startX: $('#dataX').val(),
            startY: $('#dataY').val(),
            width: $('#dataWidth').val(),
            height: $('#dataHeight').val(),
            fileId: $('#fileId').val()
        };

        jQuery.ajax({
            'cache': false,
            'type': 'POST',
            'dataType': 'json',
            'data':'data='+JSON.stringify(data),
            'success':
                function (response) {
                    parseResponse(response);
                }, 'error': function (response) {
                alert(response.responseText);
            }, 'beforeSend': function () {
            }, 'complete': function () {
            }, 'url': url});

    });

    $(document).on('click', '.cancel-crop', function(){
        event.preventDefault();

        hideModal('.modal');
    });

    $('.modal').on('hidden.bs.modal', function (e) {
        $(this).removeData('bs.modal');
    });

    $(document).on('change', '.change-order-status', function (event) {
        event.preventDefault();
        var id = $(this).attr('data-id');
        $.ajax({
                type: "get",
                url: '/catalog/catalog-order/change-status?id=' + id,
                success: function(data){
                }
        });
    });

    $(document).on('click', '.btn-add-option', function (event) {
        event.preventDefault();
        var that = this;
        var url = $(that).data('url');
        var val = $(that).parents('.add-option-block').find('select').val();
        var price = $(that).parents('.add-option-block').find('input[name="memory-price"]').val();
        var new_price = $(that).parents('.add-option-block').find('input[name="memory-new_price"]').val();
        jQuery.ajax({
            'cache': false,
            'type': 'POST',
            'dataType': 'json',
            'data': 'type='+val+'&price='+price+'&newprice='+new_price,
            'success':
                function (response) {
                    parseResponse(response);
                }, 'error': function (response) {
                alert(response.responseText);
            }, 'beforeSend': function () {
            }, 'complete': function () {
            }, 'url': url});
    });

    $(document).on('click', '.delete-option', function(event) {
        event.preventDefault();

        if (confirm("Подтвердите удаление опции")) {
            $(this).parents('.options-row').remove();
        }

    });

    $(".options-table").sortable({
        items: ' tr',
        stop: function (event, ui) {
            var i = 0;
            $.each($(this).find('tr input').not('.with-lang').not("[data-attribute='new_price']"), function()
            {
                var modelName = $('.model-class-name').val();
                console.log(i);
                var name = modelName +
                    '[' + $(this).attr('data-type') + ']' +
                    '[' + i + ']' +
                    '[' + $(this).attr('data-option') + ']' +
                    '[' + $(this).attr('data-id') + ']';
                if($(this).attr('data-lang') != ''){
                    name += '[' + $(this).attr('data-lang') + ']';
                }
                name += '[' + $(this).attr('data-attribute') + ']';
                $(this).attr('name', name);
                $.each($(this).parents('tr.options-row').find('input').not('#' + $(this).attr('id')), function() {
                    var name = modelName +
                        '[' + $(this).attr('data-type') + ']' +
                        '[' + i + ']' +
                        '[' + $(this).attr('data-option') + ']' +
                        '[' + $(this).attr('data-id') + ']';
                    if($(this).attr('data-lang') != ''){
                        name += '[' + $(this).attr('data-lang') + ']';
                    }
                    name += '[' + $(this).attr('data-attribute') + ']';
                    $(this).attr('name', name);
                });
                i++;
            });
        }
    });
});

function prepareUpload(event) {
    files.push(event.target);
}

function submitLinkForm(el) {
    var form = $(el).parents('#contentWidgetForm');
    var formHide = form;

    var data = new FormData();

    var dataForm = $(formHide).serializeArray();

    for (var i = 0, ilen = dataForm.length; i < ilen; i++) {
        data.append(dataForm[i].name, dataForm[i].value);
    }

    if (files) {
        for(var i=0; i<files.length; i++) {
            if (files[i].files.length) {
                $.each(files[i].files, function (k, v) {
                    data.append(files[i].name, v);
                });
            }
        }
    }
    console.log(data);

    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        dataType: 'json',
        processData: false,
        contentType: false,
        data: data,
        success: parseResponse,
        error: function (response) {
            alert(response.responseText);
        }
    });
}

function openView(el) {
    $("#contentConstructorViewModal").modal({
        show: false
    }).modal('show');
}

function hideModal(elem) {
    $(elem).modal('hide');
}

function parseResponse(response) {
    if (response.replaces instanceof Array) {
        for (var i = 0, ilen = response.replaces.length; i < ilen; i++) {
            $(response.replaces[i].what).replaceWith(response.replaces[i].data);
            }
        }
        if (response.append instanceof Array) {
            for (i = 0, ilen = response.append.length; i < ilen; i++) {
                $(response.append[i].what).append(response.append[i].data);
            }
        }
        if (response.content instanceof Array) {
            for (i = 0, ilen = response.content.length; i < ilen; i++) {
                $(response.content[i].what).html(response.content[i].data);
            }
        }
        if (response.js) {
            $("body").append(response.js);
        }
        if (response.hide) {
            $('.modal-header .close').trigger('click');
        }
        if (response.refresh) {
            window.location.reload(true);
        }
        if (response.redirect) {
            window.location.href = response.redirect;
        }
}

function initStoreProductImageSorting()
{
    if ($('.file-preview-thumbnails').length) {
        $('.file-preview-thumbnails').sortable({
            update: function (event, ui) {
                saveImageSort();
            }
        });
    }
}

function saveImageSort()
{
    var url = $('#urlForSorting').val();
    var data = $(".kv-file-remove.btn").map(
        function () {return $(this).data('key');}
    ).get().join(",");


    jQuery.ajax({
        'cache': false,
        'type': 'POST',
        'dataType': 'json',
        'data': 'sort='+data,
        'success':
            function (response) {
                parseResponse(response);
            }, 'error': function (response) {
            alert(response.responseText);
        }, 'beforeSend': function () {
        }, 'complete': function () {
        }, 'url': url});
}