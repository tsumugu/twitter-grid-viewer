<template>
  <div class="home">
    <div id="notlogined" v-if="this.credential==null">
      <button v-on:click="signin">Sign in with Twitter</button>
    </div>
    <div v-else>
      <!--<div class="button-wrapper"><button v-on:click="searchTweets">search</button></div>-->
      <div class="row">
        <!--
        <a v-for="t in imageList" :key="t.imgUrl" :href="t.tweetUrl"><img :src="t.imgUrl" style="width:100%"></a>
        -->
        <div v-for="(c, cindex) in columnList" :key="cindex" class="column">
          <div v-for="(t, rindex) in c" :key="rindex" class="item"><a :href="t.tweetUrl" target="_blank"><img :src="t.imgUrl" style="width:100%"></a><div class="item__multi-icon"><span class="material-icons">layers</span><p>4</p></div><button class="item__fav-button" v-bind:class="{favorited: t.favorited}" v-on:click="createFav(cindex, rindex, t.id)">♡</button></div>
        </div>
      </div>
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
      maxId: null
    }
  },
  watch: {
    imageList: function() {
      // v-forでの表示のために7個ごとに区切った配列を整形する
      this.columnList = this.splitArray(this.imageList, this.imageList.length/4)
      this.columnList.splice()
    }
  },
  methods: {
    signin() {
      signInWithRedirect(this.auth, this.provider)
    },
    createFav(column_index, row_index, tweetid) {
      axios.get('https://tsumugu2626.xyz/twittergridviewer/createfav.php?access_token='+this.credential.accessToken+'&access_token_secret='+this.credential.secret+'&tweetid='+tweetid)
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
      axios.get('https://tsumugu2626.xyz/twittergridviewer/searchtweets.php?access_token='+this.credential.accessToken+'&access_token_secret='+this.credential.secret+'&q='+encodeURI('list:1439526586533900296 min_faves:1000'))
      .then(res => {
        this.processRes(res, true)
      })
      .catch(err => {
        console.log(err)
      });
    },
    loadmoreTweets() {
      axios.get('https://tsumugu2626.xyz/twittergridviewer/searchtweets.php?access_token='+this.credential.accessToken+'&access_token_secret='+this.credential.secret+'&q='+encodeURI('list:1439526586533900296 min_faves:1000')+'&max_id='+this.maxId)
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
        const media = t.entities.media
        if (media != undefined) {
          const tweet_id = t.id_str
          const imgNum = media.length
          const tweetUrl = "https://"+media[0].display_url
          const imgUrl = media[0].media_url_https
          const size = media[0].sizes.large
          const favorited = t.favorited
          this.imageList.push({
            "id": tweet_id,
            "imgNum": imgNum,
            "tweetUrl": tweetUrl,
            "imgUrl": imgUrl,
            "size": size,
            "favorited": favorited
          })
        }
      })
      this.imageList.splice()
    },
    splitArray(arr, len) {
      // 参考 https://teratail.com/questions/230247
      const size = Math.ceil(arr.length / len);
      return [...new Array(size)].map((_, i) => arr.slice(i * len, (i + 1) * len));
    }
  },
  mounted() {
    this.auth = getAuth()
    this.provider = new TwitterAuthProvider()
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
  width: 20%;
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
    width: 32px;
    height: 32px;
    border-radius: 50px;
    border: none;
    background-color: #fefefe;
    opacity: 0.8;
    text-align: center;
    vertical-align: middle;
  }
  > .favorited, &__fav-button:hover {
    color: #fefefe;
    background-color: #d90000;
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
}
</style>
