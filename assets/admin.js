jQuery(document).ready(function($) {
    $('#anilist_fetch').on('click', function() {
        const id = $('#anilist_id').val();
        const post_id = $('#post_ID').val();
        const post_type = $('#post_type').val();

        if (!id) {
            alert('Inserisci un ID valido.');
            return;
        }

        $.ajax({
            url: anilistAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'anilist_fetch',
                nonce: anilistAjax.nonce,
                id: id,
                type: post_type,
                post_id: post_id
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#title').val(data.title.romaji || data.title.english);
                    wp.data.dispatch('core/editor').editPost({ content: data.description });
                    alert('Dati importati con successo e salvati nei campi personalizzati!');
                } else {
                    alert('Errore: ' + response.data.message);
                }
            },
            error: function() {
                alert('Errore durante la richiesta.');
            }
        });
    });
});
