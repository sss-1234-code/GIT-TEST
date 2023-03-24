

jQuery(document).ready(function ($) {            
   
    if (extradata.order_status == "on-hold" && extradata.transaction_id!="") {
        jQuery('.cpmw_loader_wrap').show();
        jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();
        jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.payment_msg + "</span>");
        jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_sucess')
    }
    else if(extradata.is_paid == 1) {
        jQuery('.cpmw_loader_wrap').show();
        jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();
        jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.payment_msg + "</span>");
        jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_sucess')
    }
    else if (extradata.order_status == "cancelled") {
        jQuery('.cpmw_loader_wrap').show();
        jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();
        jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.rejected_msg + "</span>");
        jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_rejected')
    }
    else {
        var price = extradata.in_crypto;
        var custom_price = price;

        if (typeof window.ethereum === 'undefined' || typeof web3 === 'undefined') {          
            jQuery('.cmpw_meta_connect').show();
            jQuery('.cmpw_meta_connect .cpmw_connect_btn button').on("click", function (params) {
                const el = document.createElement('div')
                el.innerHTML = "<a href='https://chrome.google.com/webstore/detail/metamask/nkbihfbeogaeaoehlefnkodbefgpgknn?hl=en' target='_blank'>Click Here </a> to install MetaMask extention"

                Swal.fire({
                    title: extradata.const_msg.ext_not_detected,   
                    html: el,
                    icon: "warning",
                })
            })
           
           
        }
        else {
            var meta_acoount = String(ethereum.selectedAddress);
            console.log(meta_acoount)
            if (ethereum.selectedAddress == undefined) {
                jQuery('.cmpw_meta_connect').show();
                jQuery('.cmpw_meta_connect .cpmw_connect_btn button').on("click", function (params) {
                    cmp_connect(custom_price, extradata.recever);
                })
            }
            else {                              
                const defined_network = extradata.network;
                if (ethereum.chainId == defined_network) {
                    jQuery('.cmpw_meta_wrapper .active-chain p.cpmw_active_chain').html(extradata.network_name);
                }
                // jQuery('.cpmw_loader_wrap').show();
                jQuery('.cmpw_meta_wrapper').show();
                jQuery('.cmpw_meta_wrapper .connected-account .account-address').append(meta_acoount)
                jQuery('.pay-btn-wrapper button').on("click", function (params) {      
                    const desiredNetwork = extradata.network; // '1' is the Ethereum main network ID. 
                    if (ethereum.chainId != desiredNetwork) {

                        Swal.fire({
                            title: extradata.const_msg.required_network,
                            text: extradata.const_msg.switch_network,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'Ok',
                            reverseButtons: true,

                        }).then((result) => {
                            if (result.isConfirmed) {
                                cpmw_change_network(extradata.network);
                            }
                        })


                    }
                    else{                                 

                        cmp_metamask(custom_price, extradata.recever);  
                    }                
                   
                })

            }
        }
    }
})

//Main function to call metamask extention
function cmp_metamask(price, user_val) {
    if (price == extradata.in_crypto && user_val == extradata.recever) {
    jQuery('.pay-btn-wrapper button').removeAttr('disabled');  
    var user_account = user_val; 
    const yourAddress = user_account;  
    const value = ethers.utils.parseEther(price)._hex;       
        const confirm_payment = document.createElement('div')
        confirm_payment.innerHTML = extradata.in_crypto + extradata.currency_symbol
        Swal.fire({
            title: extradata.const_msg.confirm_order,
            html: confirm_payment,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: 'Confirm',           
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {              
         
        ethereum.request({ method: 'eth_requestAccounts' }).catch(function (reason) {
                if (reason === 'User rejected provider access') {
                    alert("user_rjct") // The user didn't want to sign in!                    
                } else {                    
                    alert('There was an issue signing you in.')// This shouldn't happen, so you might want to log this...
                }
        }).then(function (accounts) {   // In the case they approve the log-in request, you'll receive their accounts:                
            const account = accounts[0]
            sendEtherFrom(account, function (err, transaction) {
                if (err) {
                    jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>Sorry you weren't able to contribute!</span>");
                    return ;
                }
                const confirm_message = document.createElement('div')
                confirm_message.innerHTML = extradata.confirm_msg;  
                Swal.fire({
                    title: extradata.confirm_msg, 
                    timerProgressBar: true,                  
                    didOpen: () => {
                        Swal.showLoading()                       
                    },
                })
                jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();
                //jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.confirm_msg + "</span>");                
            })

        })
            }
        }) 
    
    //Send Default chain tokens
    function sendEtherFrom(account, callback) {              
        Swal.fire({            
            title: extradata.confirm_msg, 
            didOpen: () => {
                Swal.showLoading()
            }, 
            imageUrl: extradata.url + "/assets/images/metamask.png",
            allowOutsideClick: false,
        })
        const method = 'eth_sendTransaction'
        const parameters = [{
            from: account,
            to: yourAddress,
            value: value,
            gas: '0xa028',
        }]
        const from = account

        // Now putting it all together into an RPC request:
        const payload = {
            method: method,
            params: parameters,
            from: from,
        }       
        let send_token_amount = extradata.in_crypto
        let to_address = yourAddress//extradata.recever)       
        let contract_address = extradata.token_address;
        window.ethersProvider = new ethers.providers.Web3Provider(window.ethereum)
        let default_currency=["ETH","BNB"];        
        if (jQuery.inArray(extradata.currency_symbol, default_currency) == -1){        
            cpmw_send_token(contract_address, send_token_amount, to_address);
        }       
        else{
        // Methods that require user authorization like this one will prompt a user interaction.
        // Other methods (like reading from the blockchain) may not.
        try{
            const provider = new ethers.providers.Web3Provider(window.ethereum, "any");
            const signer = provider.getSigner()           
            var secret_code="" ;
                const tx = {
                    from: from,
                    to: yourAddress,
                    value: value,                                     
                    gasLimit: ethers.utils.hexlify("0x5208"), // 21000
                   
                }

            const trans = signer.sendTransaction(tx).then( async function (res) {
                Swal.close()
                const process_messsage = document.createElement('div')
                process_messsage.innerHTML = '<p class="cpmw_transaction_note">' + extradata.const_msg.notice_msg + '</p>';
                Swal.fire({
                    title: extradata.process_msg,                   
                    imageUrl: extradata.url + "/assets/images/metamask.png",
                    footer: process_messsage,                    
                    didOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false,
                })
                jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();                          
                 const currentBlock = await provider.getBlockNumber()               
                var request_data = {
                    'action': 'cpmw_get_transaction_hash',
                    'nonce': extradata.nonce,
                    'order_id': extradata.id,
                    'transaction_id': res.hash,                   
                };
                jQuery.ajax({
                    type: "post",
                    dataType: "json",
                    url: extradata.ajax,
                    data: request_data,
                    success: function (data) {
                        secret_code = data.secret_code
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        console.log("Status: " + textStatus + "Error: " + errorThrown);
                    }
                }) 
           return res.wait();                
            }).then(function (tx) {              
                Swal.close()
                Swal.fire({
                    title: extradata.payment_msg,
                    imageUrl: extradata.url + "/assets/images/metamask.png",                    
                })
                jQuery('.cmpw_meta_wrapper').hide();
                 jQuery('.cpmw_loader_wrap').show();                
                jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.payment_msg + "</span>");
                jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_sucess')
                cpmw_status_loop(tx.transactionHash, false, secret_code);                
            }).catch(function (error) {
                if (error.code == "4001") {
                    Swal.close()
                    Swal.fire({
                        title: extradata.rejected_msg,
                        imageUrl: extradata.url + "/assets/images/metamask.png",                       
                        timer: 2000,                       
                    })
                    jQuery('.cmpw_meta_wrapper').hide();                   
                    jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.rejected_msg + "</span>");
                    jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_rejected')
                    cpmw_status_loop(false, true,"");
                    return;
                }
                else {
                    Swal.close()
                    Swal.fire({
                        title: 'Error code:' + error.code,
                        text: error.message,
                        icon: 'error'

                    })

                }
            });       
        }
        catch(erro){
            console.log(erro)
        }

    }
    }

}
}



//Change metamask network if not on desired network
async function cpmw_change_network(chain_id) {
    let ethereum = window.ethereum;    
    try {
        const chain_change = await ethereum.request({
            method: 'wallet_switchEthereumChain',
            params: [{ chainId: chain_id }],
        });
        jQuery('.pay-btn-wrapper button').attr('disabled', 'disabled');
        location.reload();
    } catch (switchError) {
        // This error code indicates that the chain has not been added to MetaMask.
        if (switchError.code === 4902) {
            try {
                ethereum.request({
                    method: 'wallet_addEthereumChain',
                    params: Array(JSON.parse(extradata.network_data)),
                });
            } catch (addError) {
                // handle "add" error
            }
        }
        // handle other "switch" errors
    }
}
//Popup user login for metamask
async function cmp_connect(custom_price, recever) {
    Swal.close()
    Swal.fire({
        title: extradata.const_msg.connection_establish,
        didOpen: () => {
            Swal.showLoading()
        },
        
        allowOutsideClick: false,
    })
    const accountss = await ethereum.request({ method: 'eth_requestAccounts' })
        .catch((error) => {
            if (error.code === 4001) {
                // EIP-1193 userRejectedRequest error
                console.log('Please connect to MetaMask.');
            } else {               
                console.error(error);
            }
        }).then(function (accounts) {
    //console.log(accounts)
    if (accounts[0] != undefined) {
        Swal.close()
        jQuery('.cmpw_meta_wrapper .connected-account .account-address').append(accounts);
        jQuery('.cmpw_meta_connect').hide();
        jQuery('.cmpw_meta_wrapper').show();
        jQuery('.pay-btn-wrapper button').on("click", function (params) {          
                         const desiredNetwork = extradata.network; // '1' is the Ethereum main network ID. 
                    if (ethereum.chainId != desiredNetwork) {

                        Swal.fire({
                            title: extradata.const_msg.required_network,
                            text: extradata.const_msg.switch_network,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'Ok',
                            reverseButtons: true,

                        }).then((result) => {
                            if (result.isConfirmed) {
                                cpmw_change_network(extradata.network);
                            }
                        })


                    }
                    else{                                 

                        cmp_metamask(custom_price, recever);  
                    }                
                   
            
        })
    }
});

}

//Confirm payment in Woocommerece
function cpmw_status_loop(transaction, rejected, secret_code) {
    let sender = String(window.ethereum.selectedAddress);  
    var request_data = {
        'action': 'cpmw_payment_verify',
        'nonce': extradata.nonce,
        'order_id': extradata.id,
        'payment_status': extradata.payment_status,
        'payment_processed': transaction,
        'rejected_transaction': rejected,
        'selected_network': ethereum.chainId,
        'sender': sender,
        'recever': extradata.recever,
        'amount': extradata.in_crypto,
        'secret_code': secret_code,
    };
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: extradata.ajax,
        data: request_data,
        success: function (data) {
            Swal.close()
            if (data.order_status == "cancelled") {
                jQuery('.cpmw_loader_wrap').show();
                jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();                
                jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.rejected_msg + "</span>");
                jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_rejected')
            }
            if (data.is_paid == true) {
                jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();                               
                jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.payment_msg + "</span>");
                jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_sucess')
               // jQuery('.cpmw_loader_wrap .cpmw_loader p.cpmw_transaction_note').hide();               
                if (extradata.redirect != "") {
                    window.location.href = extradata.redirect;
                }
                else{                     
                    location.reload();
                } 
            }

        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            console.log("Status: " + textStatus + "Error: " + errorThrown);
        }
    })
}

//Send Tokens 
async function cpmw_send_token(contract_address,send_token_amount,to_address) {  
        if (contract_address) {
            if (send_token_amount == extradata.in_crypto && to_address == extradata.recever){
            // The ERC-20 ABI
            try{
            var abi = [
                "function name() view returns (string)",
                "function symbol() view returns (string)",
                "function gimmeSome() external",
                "function balanceOf(address _owner) public view returns (uint256 balance)",
                "function transfer(address _to, uint256 _value) public returns (bool success)",
                "function decimals() view returns (uint256)",
            ];
                const provider = new ethers.providers.Web3Provider(window.ethereum, "any");
                await provider.send("eth_requestAccounts", []);
                const signer = provider.getSigner();
                let userAddress = await signer.getAddress();           
                var address = contract_address;
                var contract = new ethers.Contract(address, abi, signer);   
                var secret_code = ""         
            // Listen for Transfer events (triggered after the transaction)
            contract.ontransfer = function (from, to, amount) {
                var text = ethers.utils.formatEther(amount);                                         
            }
                const decimals = await contract.decimals();               
            // Get the balance of the wallet before the transfer
                var targetAddress = to_address;
                var amount = ethers.utils.parseUnits(send_token_amount, decimals);              
                let befyblc = await contract.balanceOf(userAddress).then(function (balance) {                   
                    var text = ethers.utils.formatUnits(balance,decimals);                                
                    if (Number(text) >= send_token_amount) {                 
                  contract.transfer(targetAddress, amount).then(function (tx) {
                      Swal.close()
                      const process_messsage = document.createElement('div')
                      process_messsage.innerHTML = '<p class="cpmw_transaction_note">' + extradata.const_msg.notice_msg + '</p>';
                      Swal.fire({
                          title: extradata.process_msg,
                          imageUrl: extradata.url + "/assets/images/metamask.png",
                          footer: process_messsage,
                          didOpen: () => {
                              Swal.showLoading()
                          },
                          allowOutsideClick: false,
                      })                  
                      // Show the pending transaction   
                      var request_data = {
                          'action': 'cpmw_get_transaction_hash',
                          'nonce': extradata.nonce,
                          'order_id': extradata.id,
                          'transaction_id': tx.hash,
                      };
                      jQuery.ajax({
                          type: "post",
                          dataType: "json",
                          url: extradata.ajax,
                          data: request_data,
                          success: function (data) {
                              secret_code = data.secret_code
                          },
                          error: function (XMLHttpRequest, textStatus, errorThrown) {
                              console.log("Status: " + textStatus + "Error: " + errorThrown);
                          }
                      })                                     
                      return tx.wait();
                  }).then(function (tx) {                   
                      // Get the balance of the provider after the transfer
                      contract.balanceOf(userAddress).then(function (balance) {
                          var text = ethers.utils.formatUnits(balance, 18);    
                         // console.log(tx);                   
                          Swal.close()
                          Swal.fire({
                              title: extradata.payment_msg,
                              imageUrl: extradata.url + "/assets/images/metamask.png",                              
                          })
                          jQuery('.cmpw_meta_wrapper').hide();
                          jQuery('.cpmw_loader_wrap').show();    
                          jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.payment_msg + "</span>");
                          jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_sucess')
                          cpmw_status_loop(tx.transactionHash, false, secret_code);
                      })
                  }).catch(function (error) {                      
                      if (error.code =="4001"){
                          Swal.close()
                          Swal.fire({
                              title: extradata.rejected_msg,
                              imageUrl: extradata.url + "/assets/images/metamask.png",                              
                              timer: 2000,
                          })
                          jQuery('.cmpw_meta_wrapper').hide();
                       jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();
                      jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.rejected_msg + "</span>");
                      jQuery('.cpmw_loader_wrap .cpmw_loader h2 span').addClass('cpmw_payment_rejected')
                      cpmw_status_loop(false, true,"");
                      return; 
                      }
                      else {
                          Swal.close()
                          Swal.fire({
                              title: 'Error code:' + error.code,
                              text: error.message,
                              icon: 'error'

                          })

                      }                      
                  });
               }
              else{
                        Swal.close()
                        Swal.fire({
                            title: extradata.const_msg.insufficient_balance + text,
                            imageUrl: extradata.url + "/assets/images/metamask.png",                         
                        })
                  jQuery('.cpmw_loader_wrap .cpmw_loader>div').hide();
                  jQuery('.cpmw_loader_wrap .cpmw_loader h2').html("<span>" + extradata.const_msg.insufficient_balance  + text + "</span>");                  
              } 
          })
        }
        catch(error){
                console.log(error)               
           
        }           
           
        } }
     
  
}


