// ============================================
// CONTROL DE MÚSICA - SOLO PARA USUARIO
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const esAdmin = document.body.dataset.rol === 'admin';
    
    if(!esAdmin) {
        const audio = document.getElementById('musicaFondo');
        
        if(audio) {
            audio.volume = 0.5;
            
            const playPromise = audio.play();
            
            if(playPromise !== undefined) {
                playPromise.catch(function(error) {
                    console.log('⚠️ Autoplay bloqueado. Esperando interacción...');
                    
                    const playOnClick = function() {
                        audio.play();
                        document.removeEventListener('click', playOnClick);
                        document.removeEventListener('touchstart', playOnClick);
                    };
                    
                    document.addEventListener('click', playOnClick);
                    document.addEventListener('touchstart', playOnClick);
                });
            }
            
            const controlVolumen = document.getElementById('controlVolumen');
            if(controlVolumen) {
                controlVolumen.addEventListener('input', function(e) {
                    audio.volume = parseFloat(e.target.value);
                });
            }
        }
    }
});

console.log('🎵 Sistema de música cargado');