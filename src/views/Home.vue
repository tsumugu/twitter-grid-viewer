<template>
  <div class="home">
    <div class="slideshow" v-if="this.isOpeningModal">
      <button class="slideshow__button slideshow__close-button" v-on:click="()=>{this.modalImageList=[]; this.modalImageIndex=null;}"><span class="material-icons-outlined">close</span></button>
      <button class="slideshow__button slideshow__minus-button" v-on:click="()=>{
        if (this.modalImageIndex > 0) {
          this.modalImageIndex--;
        }
      }" v-show="this.modalImageIndex>0"><span class="material-icons-outlined">chevron_left</span></button>
      <img class="slideshow__image" v-lazy="this.modalImageList[this.modalImageIndex]">
      <button class="slideshow__button slideshow__plus-button" v-on:click="()=>{
        if (this.modalImageList.length-1 > this.modalImageIndex) {
          this.modalImageIndex++;
        }
      }" v-show="this.modalImageList.length-1>this.modalImageIndex"><span class="material-icons-outlined">chevron_right</span></button>
    </div>
    <div class="notlogined" v-if="this.credential==null">
      <button v-on:click="signin">Sign in with Twitter</button>
    </div>
    <div v-else>
      <div class="menubar">
        <button class="menubar__open-button" v-bind:class="{expand_radius: isExp, antonym_radius: isAnt}" v-on:click="expSearch"><span class="material-icons-outlined">search</span></button>
        <div class="menubar__band" v-bind:class="{expand: isExp, antonym: isAnt}">
          <div class="menubar__band__contents" v-bind:class="{expand_form: isExp, antonym_form: isAnt}">
            <input type="text" v-model="q">
            <button v-on:click="searchTweets">検索</button>
            <div><label for="checkbox"><input type="checkbox" id="checkbox" v-model="isFavoritedOnly">未いいねのみ表示</label></div>
          </div>
        </div>
      </div>
      <div class="row">
        <div v-for="(c, cindex) in columnList" :key="cindex" class="column">
          <div v-for="(t, rindex) in c" :key="rindex" class="item">
            <img v-lazy="t.imgUrl" style="width:100%" v-on:click="openModal(t.imgUrlList)">
            <div class="item__author-info" v-show="t.isShowAuthorInfoFadein||t.isShowAuthorInfoFadeout" v-bind:class="{fadein: t.isShowAuthorInfoFadein, fadeout: t.isShowAuthorInfoFadeout}">
              <div class="item__author-info__top-wrap">
                <img class="item__author-info__top-wrap__icon" v-lazy="t.user.profileImgUrl">
                <div class="item__author-info__top-wrap__names">
                  <p class="item__author-info__top-wrap__names__name">{{t.user.name}}</p>
                  <p class="item__author-info__top-wrap__names__screen-name">@{{t.user.screen_name}}</p>
                </div>
              </div>
              <p class="item__author-info__text"><a :href="t.tweetUrl" target="_blank">{{t.text}}</a></p>
            </div>
            <div class="item__multi-icon" v-show="t.imgNum>1"><span class="material-icons">layers</span><p>{{t.imgNum}}</p></div>
            <button class="item__fav-button" v-bind:class="{favorited: t.favorited}" v-on:click="createFav(cindex, rindex, t.id)"><span class="material-icons-outlined"><span class="material-icons-outlined">favorite_border</span></span></button>
            <button class="item__show-author-info-button" v-on:click="showAuthorInfo(cindex, rindex)"><span class="material-icons-outlined">info</span></button></div></div></div>
      <div class="button-wrapper"><button v-on:click="loadmoreTweets">さらに読み込む</button></div>
    </div>
  </div>
</template>

<script>
import axios from "axios"
import { getAuth, signInWithRedirect, getRedirectResult, onAuthStateChanged, TwitterAuthProvider } from "firebase/auth"

export default {
  name: 'Home',
  data () {
    return {
      auth: null,
      provider: null,
      credential: null,
      imageList: [],
      columnList: [],
      maxId: null,
      modalImageList: [],
      modalImageIndex: 0,
      isExp: false,
      isAnt: false,
      q: "list:1439526586533900296 min_faves:1000",
      isFavoritedOnly: false,
      isOpeningModal: false
    }
  },
  watch: {
    imageList: function() {
      this.makeDisplayList();
    },
    isFavoritedOnly: function() {
      this.makeDisplayList();
    },
    q: function() {
      // 保存しておく
      localStorage.setItem('q', this.q)
    },
    modalImageList: function() {
      if (this.modalImageList.length>0 && this.modalImageIndex != null) {
        this.isOpeningModal = true;
      } else {
        this.isOpeningModal = false;
      }
      this.togglePreventScroll();
    }
  },
  methods: {
    signin() {
      signInWithRedirect(this.auth, this.provider)
    },
    makeDisplayList() {
      // v-forでの表示のために7個ごとに区切った配列を整形する
      let list = this.imageList
      if (this.isFavoritedOnly) {
        list = this.imageList.filter(e=>!e.favorited)
      }
      this.columnList = this.splitArray(list, list.length/4)
      this.columnList.splice()
    },
    expSearch() {
      if (!this.isExp && !this.isAnt) {
        this.isExp = true;
        this.isAnt = false;
      } else if (this.isExp && !this.isAnt) {
        this.isExp = false;
        this.isAnt = true;
      } else if (!this.isExp && this.isAnt) {
        this.isExp = true;
        this.isAnt = false;
      }
    },
    createFav(column_index, row_index, tweetid) {
      axios.get('https://twigrid.tsumugu2626.xyz/createfav.php?access_token='+this.credential.accessToken+'&access_token_secret='+this.credential.secret+'&tweetid='+tweetid)
      .then(res => {
        // ふぁぼれてたらclassを付与する
        if (res.data.res.favorited) {
          this.columnList[column_index][row_index].favorited = true;
        }
      })
      .catch(err => {
        console.log(err)
      });
    },
    searchTweets() {
      axios.get('https://twigrid.tsumugu2626.xyz/searchtweets.php?access_token='+this.credential.accessToken+'&access_token_secret='+this.credential.secret+'&q='+encodeURI(this.q))
      .then(res => {
        this.processRes(res, true)
      })
      .catch(err => {
        console.log(err)
      });
    },
    loadmoreTweets() {
      axios.get('https://twigrid.tsumugu2626.xyz/searchtweets.php?access_token='+this.credential.accessToken+'&access_token_secret='+this.credential.secret+'&q='+encodeURI(this.q)+'&max_id='+this.maxId)
      .then(res => {
        this.processRes(res, false)
      })
      .catch(err => {
        console.log(err)
      });
      // ページ最上部に飛ばす
      window.scrollTo({
        top: 0,
        behavior: "smooth"
      })
    },
    processRes(res, isReset=true) {
      this.maxId = res.data.max_id
      //if (isReset) {
        this.imageList = []
      //}
      res.data.tweets.forEach(t=>{
        const media = t.extended_entities.media
        if (media != undefined) {
          const tweet_id = t.id_str
          const text = t.text;
          const imgNum = media.length
          const tweetUrl = "https://"+media[0].display_url
          const imgUrl = media[0].media_url_https
          const size = media[0].sizes.large
          const favorited = t.favorited
          const imgUrlList = media.map(e=>e.media_url_https);
          this.imageList.push({
            "id": tweet_id,
            "text": text,
            "imgNum": imgNum,
            "tweetUrl": tweetUrl,
            "imgUrl": imgUrl,
            "size": size,
            "favorited": favorited,
            "imgUrlList": imgUrlList,
            "user": {
              "name": t.user.name,
              "screen_name": t.user.screen_name,
              "profileImgUrl": t.user.profile_image_url_https.replace("_normal", "")
            },
            "isShowAuthorInfoFadein": false,
            "isShowAuthorInfoFadeout": false
          })
        }
      })
      this.imageList.splice()
    },
    splitArray(arr, len) {
      // 参考 https://teratail.com/questions/230247
      const size = Math.ceil(arr.length / len);
      return [...new Array(size)].map((_, i) => arr.slice(i * len, (i + 1) * len));
    },
    openModal(imgUrlList) {
      this.modalImageList = imgUrlList;
      this.modalImageIndex = 0;
    },
    preventDefaultEvent(event) {
      event.preventDefault();
    },
    togglePreventScroll() {
      if (this.isOpeningModal) {
        // モーダル背面のスクロールを抑制
        document.addEventListener('touchmove', this.preventDefaultEvent, { passive: false });
        document.addEventListener('mousewheel', this.preventDefaultEvent, { passive: false });
      } else {
        // モーダル背面のスクロールを抑制
        document.removeEventListener('touchmove', this.preventDefaultEvent, { passive: false });
        document.removeEventListener('mousewheel', this.preventDefaultEvent, { passive: false });
      }
    },
    showAuthorInfo(column_index, row_index) {
      if (!this.columnList[column_index][row_index].isShowAuthorInfoFadein && !this.columnList[column_index][row_index].isShowAuthorInfoFadeout) {
        // 両方falseは初回
        this.columnList[column_index][row_index].isShowAuthorInfoFadein = true;
        this.columnList[column_index][row_index].isShowAuthorInfoFadeout = false;
      } else if (this.columnList[column_index][row_index].isShowAuthorInfoFadein && !this.columnList[column_index][row_index].isShowAuthorInfoFadeout) {
        this.columnList[column_index][row_index].isShowAuthorInfoFadein = false;
        this.columnList[column_index][row_index].isShowAuthorInfoFadeout = true;
      } else if (!this.columnList[column_index][row_index].isShowAuthorInfoFadein && this.columnList[column_index][row_index].isShowAuthorInfoFadeout) {
        this.columnList[column_index][row_index].isShowAuthorInfoFadein = true;
        this.columnList[column_index][row_index].isShowAuthorInfoFadeout = false;
      }
    }
  },
  mounted() {
    this.auth = getAuth()
    this.provider = new TwitterAuthProvider()
    this.q = localStorage.getItem('q')
    onAuthStateChanged(this.auth, (user) => {
      if (user) {
        this.credential = JSON.parse(localStorage.getItem('credential'))
        this.searchTweets();
      }
    })
    getRedirectResult(this.auth).then((result) => {
      if (result) {
        const credential = TwitterAuthProvider.credentialFromResult(result)
        const user = result.user
        localStorage.setItem('twitterScreenName', user.reloadUserInfo.screenName)
        localStorage.setItem('twitterIconUrl', user.reloadUserInfo.photoUrl.replace("_normal", ""))
        localStorage.setItem('credential', JSON.stringify({"accessToken": credential.accessToken, "secret": credential.secret}))
        this.credential = JSON.parse(localStorage.getItem('credential'))
      }
    })
  }

}
</script>

<style lang="scss" scoped>
.home {
  margin: 0;
  width: 100vw;
  height: 100vh;
  position:relative;
  overflow: scroll;
}

.slideshow {
  position: absolute;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: #4a4a4a;
  z-index: 999;
  display: flex;
  justify-content: center;
  align-items: center;
  &__button {
    font-size: 1.8rem;
    border: none;
    background: transparent;
    color: #fefefe;
  }
  &__close-button {
    position: absolute;
    top: 0;
    left: 0;
  }
  &__minus-button {
    position: absolute;
    top: 50vh;
    left: 0;
  }
  &__image {
    width: 100%;
    height: 100%;
    object-fit: scale-down;
  }
  &__plus-button {
    position: absolute;
    top: 50vh;
    right: 0;
  }
}

.menubar {
  &__open-button {
    position: absolute;
    top: 0;
    left: 0;
    width: 50px;
    height: 50px;
    color: #fefefe;
    background-color: #0096d9;
    z-index: 1;
    border-radius: 0 50px 50px 0;
    border: none;
    > .material-icons-outlined {
      font-size: 28px !important;
    }
  }
  &__band {
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 50px;
    background-color: #0096d9;
    &__contents {
      position: absolute;
      top: 10px;
      left: 45px;
      z-index: 2;
      opacity: 0;
      > input {
        border:0;
        border:solid 1px #ccc;
        width: 300px;
        height: 28px;
      }
      > button {
        position: absolute;
        top: 0;
        left: 305px;
        width: 50px;
        height: 28px;
        background-color: #fefefe;
        border:solid 1px #ccc;
        cursor: pointer;
        outline: none;
        appearance: none;
      }
      > div {
        position: absolute;
        top: 2px;
        left: 367px;
        width: 150px;
        > label {
          user-select: none;
          color: #fefefe;
        }
      }
    }
  }
}

.button-wrapper {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.button-wrapper > button {
  margin: 16px;
  padding: 6px 20px;
  display: inline-block;
  position: relative;
  width: 30%;
  min-width: 80px;
  text-align: center;
  cursor: pointer;
  border-radius: 4px;
  border: 2px solid #0096d9;
  color: #0096d9;
  background-color: #fefefe;
  font-size: 16px;
  font-weight: 700;
}

.button-wrapper > button:hover {
  background-color: #bfdff3;
}

.item {
  position: relative;
  padding-bottom: 48px;
  &__author-info {
    padding: 16px;
    position: absolute;
    bottom: 68px;
    left: 8px;
    display: grid;
    grid-template-rows: 1fr 1fr;
    width: 90%;
    opacity: 0.9;
    background-color: #fefefe;
    border-radius: 15px;
    &__top-wrap {
      display: flex;
      justify-content: left;
      align-items: center;
      &__icon {
        margin: 0 !important;
        padding: 0;
        width: 64px !important;
        height: 64px;
        border-radius: 50px;
      }
      &__names {
        margin-left: 5px;
        display: inline;
        &__name {
          margin: 0;
          padding: 0;
          font-weight: bold;
          font-size: 18px;
          word-break: break-all;
        }
        &__screen-name {
          margin: 0;
          padding: 0;
          color: gray;
          font-size: 14px;
          word-break: break-all;
        }
      }
    }
    &__text {
      margin: 0;
      padding: 0;
      > a {
        color: #2c3e50;
      }
    }
  }
  &__multi-icon {
    position: absolute;
    /*top: 16px;*/
    top: -8px;
    right: 8px;
    width: 46px;
    height: 32px;
    border-radius: 50px;
    border: none;
    /*
    border: 1px solid #a19d9d;
    border: none;
    background-color: #a19d9d;
    */
    opacity: 0.8;
    display: flex;
    justify-content: center;
    align-items: center;
    /*color: #fefefe;*/
    background-color: #fefefe; 
    color: #a19d9d;
    font-size: 16px;
    > p {
      margin-left: 2px;
    }
    > .material-icons {
      font-size: 14px;
    }
  }
  &__fav-button {
    position: absolute;
    /* 28pxのとき */
    /*
    bottom: 14px;
    left: 8px;
    */
    /* 48pxのとき */
    bottom: 32px;
    left: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 32px;
    height: 32px;
    border-radius: 50px;
    border: none;
    background-color: #fefefe;
    color: #a19d9d;
    opacity: 0.8;
    text-align: center;
    vertical-align: middle;
    > .material-icons-outlined {
      font-size: 18px !important;
    }
  }
  > .favorited, &__fav-button:hover {
    color: #fefefe;
    background-color: #d90000;
  }
  &__show-author-info-button {
    position: absolute;
    bottom: 32px;
    left: 44px;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 32px;
    height: 32px;
    border-radius: 50px;
    border: none;
    background-color: #fefefe; 
    color: #a19d9d;
    opacity: 0.8;
    text-align: center;
    vertical-align: middle;
    > .material-icons-outlined {
      font-size: 22px !important;
    }
  }
}

/* 参考 https://www.w3schools.com/howto/tryit.asp?filename=tryhow_css_image_grid_responsive */
* {
  box-sizing: border-box;
}

.row {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  padding-right: 48px;
}
.column {
  display: inline;
  -ms-flex: 25%;
  flex: 25%;
  max-width: 25%;
  padding: 48px 0 0 48px;
}
.column img {
  margin-top: 8px;
  vertical-align: middle;
  width: 100%;
}

@media screen and (max-width: 800px) {
  .column {
    -ms-flex: 50%;
    flex: 50%;
    max-width: 50%;
  }
}

@media screen and (max-width: 600px) {
  .column {
    -ms-flex: 100%;
    flex: 100%;
    max-width: 100%;
  }
  .button-wrapper > button {
    width: calc( 100% - 96px );
  }
}

.fadein {
  animation: fadein 1000ms ease 0s 1 forwards;
}

.fadeout {
  animation: fadeout 1000ms ease 0s 1 forwards;
}

@keyframes fadein {
  100% {
    opacity: 0.9;
  }
}

@keyframes fadeout {
  0% {
    opacity: 0.9;
  }
  100% {
    opacity: 0;
  }
}

.expand {
  animation: expand 1000ms ease 0s 1 forwards;
}

.antonym {
  animation: antonym 1000ms ease 0s 1 forwards;
}

@keyframes expand {
  100% {
    width: 100%;
  }
}

@keyframes antonym {
  0% {
    width: 100%;
  }
  100% {
    width: 0;
  }
}

.expand_form {
  animation: expand-form 1000ms ease 0s 1 forwards;
}

.antonym_form {
  animation: antonym-form 1000ms ease 0s 1 forwards;
}

@keyframes expand-form {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}

@keyframes antonym-form {
  0% {
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}

.expand_radius {
  animation: expand-radius 4200ms ease 0s 1 forwards;
}

.antonym_radius {
  animation: antonym-radius 4200ms ease 0s 1 forwards;
}

@keyframes expand-radius {
  0% {
    border-radius: 0 50px 50px 0;
  }
  100% {
    border-radius: 0;
  }
}

@keyframes antonym-radius {
  0% {
    border-radius: 0;
  }
  100% {
    border-radius: 0 50px 50px 0;
  }
}
</style>
